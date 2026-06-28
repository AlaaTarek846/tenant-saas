<?php

namespace App\Services\Admin;

use App\Enums\InvoiceStatusEnum;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\Admin\InvoiceRepository;
use App\Services\Admin\Concerns\HandlesTenantScopedAdmin;
use App\Services\BaseService;
use App\Support\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService extends BaseService
{
    use HandlesTenantScopedAdmin;

    public function __construct(
        InvoiceRepository $repository,
        protected AccountingService $accountingService,
    ) {
        $this->repository = $repository;
    }

    public function allOrPaginate($resource, $groupBy = null, ?User $actor = null)
    {
        $query = $this->scopedQuery($actor)->with(['customer', 'subscription'])->withCount('payments');

        if ($search = request('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $query->latest('id');

        return allOrPaginate($query, $resource, $groupBy);
    }

    public function store(array $data, ?User $actor = null): Invoice
    {
        $items = $data['items'] ?? [];
        unset($data['items']);
        $data = $this->applyTenantId($data, $actor);
        $this->ensureRelatedModelsBelongToTenant($data, $actor);

        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = $this->repository->nextNumber((int) $data['tenant_id']);
        }

        $data = $this->calculateTotals($data, $items);

        return DB::transaction(function () use ($data, $items) {
            /** @var Invoice $invoice */
            $invoice = $this->repository->store($data);

            foreach ($items as $item) {
                $invoice->items()->create([
                    'subscription_plan_id' => $item['subscription_plan_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'],
                    'total' => ($item['quantity'] ?? 1) * $item['unit_price'],
                ]);
            }

            $invoice = $invoice->fresh()->load(['customer', 'subscription', 'items']);
            $this->recordAccountingIfIssued($invoice);

            return $invoice->load(['journalEntries.details.account']);
        });
    }

    public function show($id, ?User $actor = null): Invoice
    {
        $invoice = $this->repository->show($id);
        $this->ensureCanAccess($invoice, $actor, 'ليس لديك صلاحية الوصول لهذه الفاتورة.');

        return $invoice->load(['customer', 'subscription', 'items', 'payments', 'journalEntries.details.account']);
    }

    public function update($id, array $data, ?User $actor = null): Invoice
    {
        $invoice = $this->repository->show($id);
        $this->ensureCanAccess($invoice, $actor, 'ليس لديك صلاحية تعديل هذه الفاتورة.');
        unset($data['tenant_id'], $data['invoice_number']);

        $items = $data['items'] ?? null;
        unset($data['items']);

        if (isset($data['customer_id']) || isset($data['subscription_id'])) {
            $this->ensureRelatedModelsBelongToTenant([
                'tenant_id' => $invoice->tenant_id,
                'customer_id' => $data['customer_id'] ?? $invoice->customer_id,
                'subscription_id' => $data['subscription_id'] ?? $invoice->subscription_id,
            ], $actor);
        }

        if (is_array($items)) {
            $data = $this->calculateTotals($data, $items);
        }

        return DB::transaction(function () use ($id, $data, $items, $invoice) {
            $invoice = $this->repository->update($id, $data);

            if (is_array($items)) {
                $invoice->items()->delete();

                foreach ($items as $item) {
                    $invoice->items()->create([
                        'subscription_plan_id' => $item['subscription_plan_id'],
                        'description' => $item['description'],
                        'quantity' => $item['quantity'] ?? 1,
                        'unit_price' => $item['unit_price'],
                        'total' => ($item['quantity'] ?? 1) * $item['unit_price'],
                    ]);
                }
            }

            $invoice = $invoice->fresh()->load(['customer', 'subscription', 'items', 'payments']);
            $this->recordAccountingIfIssued($invoice);

            return $invoice->load(['journalEntries.details.account']);
        });
    }

    public function destroy($id, ?User $actor = null): bool
    {
        $invoice = $this->repository->show($id);
        $this->ensureCanAccess($invoice, $actor, 'ليس لديك صلاحية حذف هذه الفاتورة.');

        return (bool) $this->repository->destroy($id);
    }

    protected function ensureRelatedModelsBelongToTenant(array $data, ?User $actor): void
    {
        $tenantId = $data['tenant_id'] ?? TenantScope::id($actor);

        $customer = Customer::query()->find($data['customer_id'] ?? null);
        $subscription = Subscription::query()->find($data['subscription_id'] ?? null);

        if (! $customer || (int) $customer->tenant_id !== (int) $tenantId) {
            throw ValidationException::withMessages([
                'customer_id' => ['العميل غير صالح لهذه الشركة.'],
            ]);
        }

        if (! $subscription || (int) $subscription->tenant_id !== (int) $tenantId) {
            throw ValidationException::withMessages([
                'subscription_id' => ['الاشتراك غير صالح لهذه الشركة.'],
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected function calculateTotals(array $data, array $items): array
    {
        $subtotal = collect($items)->sum(fn ($item) => ($item['quantity'] ?? 1) * $item['unit_price']);
        $discount = (float) ($data['discount'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);
        $data['subtotal'] = $subtotal;
        $data['discount'] = $discount;
        $data['tax'] = $tax;
        $data['total'] = max(0, $subtotal - $discount + $tax);

        return $data;
    }

    protected function recordAccountingIfIssued(Invoice $invoice): void
    {
        if ($invoice->status === InvoiceStatusEnum::DRAFT) {
            return;
        }

        if ($invoice->hasIssuedJournalEntry()) {
            return;
        }

        $this->accountingService->recordInvoiceIssued($invoice);
    }
}
