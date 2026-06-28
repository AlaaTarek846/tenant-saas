<?php

namespace App\Services\Admin;

use App\Enums\PaymentStatusEnum;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\Admin\PaymentRepository;
use App\Services\Admin\Concerns\HandlesTenantScopedAdmin;
use App\Services\BaseService;
use App\Support\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService extends BaseService
{
    use HandlesTenantScopedAdmin;

    public function __construct(
        PaymentRepository $repository,
        protected AccountingService $accountingService,
    ) {
        $this->repository = $repository;
    }

    public function allOrPaginate($resource, $groupBy = null, ?User $actor = null)
    {
        $query = $this->scopedQuery($actor)->with(['invoice.customer']);

        if ($search = request('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('invoice', fn ($q) => $q->where('invoice_number', 'like', "%{$search}%"));
            });
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $query->latest('id');

        return allOrPaginate($query, $resource, $groupBy);
    }

    public function store(array $data, ?User $actor = null)
    {
        $data = $this->applyTenantId($data, $actor);
        $this->ensureInvoiceBelongsToTenant($data, $actor);

        return DB::transaction(function () use ($data, $actor) {
            /** @var Payment $payment */
            $payment = $this->repository->store($data);
            $payment = $payment->fresh()->load(['invoice.customer']);
            $this->recordAccountingIfPaid($payment);

            return $payment->load(['journalEntries.details.account']);
        });
    }

    public function show($id, ?User $actor = null)
    {
        $payment = $this->repository->show($id);
        $this->ensureCanAccess($payment, $actor, 'ليس لديك صلاحية الوصول لهذا الدفع.');

        return $payment->load(['invoice.customer', 'journalEntries.details.account']);
    }

    public function update($id, array $data, ?User $actor = null)
    {
        $payment = $this->repository->show($id);
        $this->ensureCanAccess($payment, $actor, 'ليس لديك صلاحية تعديل هذا الدفع.');
        unset($data['tenant_id']);

        if (isset($data['invoice_id'])) {
            $this->ensureInvoiceBelongsToTenant([
                'tenant_id' => $payment->tenant_id,
                'invoice_id' => $data['invoice_id'],
            ], $actor);
        }

        return DB::transaction(function () use ($id, $data) {
            /** @var Payment $payment */
            $payment = $this->repository->update($id, $data);
            $payment = $payment->fresh()->load(['invoice.customer']);
            $this->recordAccountingIfPaid($payment);

            return $payment->load(['journalEntries.details.account']);
        });
    }

    public function destroy($id, ?User $actor = null): bool
    {
        $payment = $this->repository->show($id);
        $this->ensureCanAccess($payment, $actor, 'ليس لديك صلاحية حذف هذا الدفع.');

        return (bool) $this->repository->destroy($id);
    }

    protected function ensureInvoiceBelongsToTenant(array $data, ?User $actor): void
    {
        $tenantId = $data['tenant_id'] ?? TenantScope::id($actor);
        $invoice = Invoice::query()->find($data['invoice_id'] ?? null);

        if (! $invoice || (int) $invoice->tenant_id !== (int) $tenantId) {
            throw ValidationException::withMessages([
                'invoice_id' => ['الفاتورة غير صالحة لهذه الشركة.'],
            ]);
        }
    }

    protected function recordAccountingIfPaid(Payment $payment): void
    {
        if ($payment->status !== PaymentStatusEnum::PAID) {
            return;
        }

        if ($payment->hasReceivedJournalEntry()) {
            return;
        }

        $this->accountingService->recordPaymentReceived($payment);
    }
}
