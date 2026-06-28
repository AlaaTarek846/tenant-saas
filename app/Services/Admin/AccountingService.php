<?php

namespace App\Services\Admin;

use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Repositories\Admin\AccountRepository;
use App\Support\ChartOfAccounts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    public function __construct(
        protected AccountRepository $accountRepository,
        protected ChartOfAccountsService $chartOfAccountsService,
    ) {}

    public function recordInvoiceIssued(Invoice $invoice): JournalEntry
    {
        if ($invoice->status === InvoiceStatusEnum::DRAFT) {
            throw ValidationException::withMessages([
                'invoice' => ['لا يمكن تسجيل قيد محاسبي لفاتورة مسودة.'],
            ]);
        }

        if ($invoice->hasIssuedJournalEntry()) {
            throw ValidationException::withMessages([
                'invoice' => ['تم تسجيل قيد إصدار الفاتورة محاسبياً مسبقاً.'],
            ]);
        }

        $this->chartOfAccountsService->seedForTenant($invoice->tenant_id);

        $amount = (float) $invoice->total;

        return $this->createBalancedEntry(
            tenantId: $invoice->tenant_id,
            reference: $invoice,
            description: "إصدار فاتورة {$invoice->invoice_number}",
            entryDate: $invoice->issue_date,
            lines: [
                [
                    'code' => ChartOfAccounts::ACCOUNTS_RECEIVABLE,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'ذمم مدينة',
                ],
                [
                    'code' => ChartOfAccounts::DEFERRED_REVENUE,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'إيرادات مؤجلة',
                ],
            ],
        );
    }

    public function recordPaymentReceived(Payment $payment): JournalEntry
    {
        if ($payment->status !== PaymentStatusEnum::PAID) {
            throw ValidationException::withMessages([
                'payment' => ['يُسجَّل القيد المحاسبي للمدفوعات بحالة paid فقط.'],
            ]);
        }

        if ($payment->hasReceivedJournalEntry()) {
            throw ValidationException::withMessages([
                'payment' => ['تم تسجيل قيد تحصيل هذه الدفعة مسبقاً.'],
            ]);
        }

        $invoice = $payment->invoice()->firstOrFail();
        $this->chartOfAccountsService->seedForTenant($payment->tenant_id);

        $amount = (float) $payment->amount;

        $entry = $this->createBalancedEntry(
            tenantId: $payment->tenant_id,
            reference: $payment,
            description: "تحصيل دفعة للفاتورة {$invoice->invoice_number}",
            entryDate: $payment->paid_at,
            lines: [
                [
                    'code' => ChartOfAccounts::CASH,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'نقدية',
                ],
                [
                    'code' => ChartOfAccounts::ACCOUNTS_RECEIVABLE,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'ذمم مدينة',
                ],
            ],
        );

        $this->syncInvoicePaymentStatus($invoice);

        return $entry;
    }

    /**
     * @return list<JournalEntry>
     */
    public function recognizeRevenue(int $tenantId, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->endOfDay();
        $this->chartOfAccountsService->seedForTenant($tenantId);

        $invoices = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('revenue_recognized_at')
            ->whereIn('status', [InvoiceStatusEnum::PENDING, InvoiceStatusEnum::PAID])
            ->whereDate('issue_date', '<=', $asOf)
            ->get();

        $entries = [];

        foreach ($invoices as $invoice) {
            if ($invoice->hasRevenueRecognitionJournalEntry()) {
                continue;
            }

            $amount = (float) $invoice->total;

            if ($amount <= 0) {
                $invoice->update(['revenue_recognized_at' => now()]);
                continue;
            }

            if (! $invoice->hasIssuedJournalEntry()) {
                $this->recordInvoiceIssued($invoice);
            }

            $entry = $this->createBalancedEntry(
                tenantId: $tenantId,
                reference: $invoice,
                description: "اعتراف بالإيراد — {$invoice->invoice_number}",
                entryDate: $asOf,
                lines: [
                    [
                        'code' => ChartOfAccounts::DEFERRED_REVENUE,
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => 'إيرادات مؤجلة',
                    ],
                    [
                        'code' => ChartOfAccounts::SUBSCRIPTION_REVENUE,
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => 'إيرادات اشتراكات',
                    ],
                ],
            );

            $invoice->update(['revenue_recognized_at' => now()]);
            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * @param  list<array{account_id: int, debit: float, credit: float, description?: string|null}>  $lines
     */
    public function createManualEntry(int $tenantId, string $description, Carbon|string $entryDate, array $lines): JournalEntry
    {
        $this->chartOfAccountsService->seedForTenant($tenantId);

        $accountLines = [];
        foreach ($lines as $line) {
            $account = $this->accountRepository->show($line['account_id']);
            if ((int) $account->tenant_id !== $tenantId) {
                throw ValidationException::withMessages([
                    'lines' => ['أحد الحسابات لا ينتمي لهذه الشركة.'],
                ]);
            }

            $accountLines[] = [
                'code' => $account->code,
                'debit' => (float) ($line['debit'] ?? 0),
                'credit' => (float) ($line['credit'] ?? 0),
                'description' => $line['description'] ?? null,
            ];
        }

        return $this->createBalancedEntry(
            tenantId: $tenantId,
            reference: null,
            description: $description,
            entryDate: $entryDate,
            lines: $accountLines,
        );
    }

    /**
     * @param  list<array{code: string, debit: float, credit: float, description?: string|null}>  $lines
     */
    protected function createBalancedEntry(
        int $tenantId,
        ?Model $reference,
        string $description,
        Carbon|string $entryDate,
        array $lines,
    ): JournalEntry {
        $totalDebit = round(collect($lines)->sum('debit'), 2);
        $totalCredit = round(collect($lines)->sum('credit'), 2);

        if ($totalDebit !== $totalCredit || $totalDebit <= 0) {
            throw ValidationException::withMessages([
                'journal' => ['القيد المحاسبي غير متوازن.'],
            ]);
        }

        return DB::transaction(function () use ($tenantId, $reference, $description, $entryDate, $lines) {
            /** @var JournalEntry $entry */
            $entry = JournalEntry::query()->create([
                'tenant_id' => $tenantId,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'description' => $description,
                'entry_date' => $entryDate,
            ]);

            foreach ($lines as $line) {
                $account = $this->accountRepository->findByCode($tenantId, $line['code']);

                $entry->details()->create([
                    'account_id' => $account->id,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry->load('details.account');
        });
    }

    protected function syncInvoicePaymentStatus(Invoice $invoice): void
    {
        $paidTotal = (float) $invoice->payments()
            ->where('status', PaymentStatusEnum::PAID)
            ->sum('amount');

        if ($paidTotal >= (float) $invoice->total) {
            $invoice->update(['status' => InvoiceStatusEnum::PAID]);
        }
    }
}
