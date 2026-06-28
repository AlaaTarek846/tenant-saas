<?php

namespace App\Services\Admin;

use App\Enums\BillingCycleEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\Admin\InvoiceRepository;
use App\Support\TenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function __construct(
        protected InvoiceRepository $invoiceRepository,
        protected AccountingService $accountingService,
        protected ChartOfAccountsService $chartOfAccountsService,
    ) {}

    /**
     * @return list<array{subscription_id: int, invoice_id: int, invoice_number: string, total: string}>
     */
    public function generateInvoices(?User $actor = null, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->toDateString();
        $tenantId = TenantScope::isScoped($actor) ? TenantScope::id($actor) : null;

        $query = Subscription::query()
            ->with(['customer', 'subscriptionPlan'])
            ->where('status', SubscriptionStatusEnum::ACTIVE)
            ->whereDate('next_billing_date', '<=', $asOf);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $generated = [];

        foreach ($query->get() as $subscription) {
            $generated[] = DB::transaction(function () use ($subscription) {
                return $this->billSubscription($subscription);
            });
        }

        return $generated;
    }

    /**
     * @return array{subscription_id: int, invoice_id: int, invoice_number: string, total: string}
     */
    protected function billSubscription(Subscription $subscription): array
    {
        $plan = $subscription->subscriptionPlan;
        $issueDate = $subscription->next_billing_date->copy();
        $dueDate = $issueDate->copy()->addDays(7);
        $amount = (float) $plan->price;

        $this->chartOfAccountsService->seedForTenant($subscription->tenant_id);

        /** @var Invoice $invoice */
        $invoice = $this->invoiceRepository->store([
            'tenant_id' => $subscription->tenant_id,
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $this->invoiceRepository->nextNumber($subscription->tenant_id),
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => $amount,
            'discount' => 0,
            'tax' => 0,
            'total' => $amount,
            'status' => InvoiceStatusEnum::PENDING,
        ]);

        $invoice->items()->create([
            'subscription_plan_id' => $plan->id,
            'description' => "{$plan->name} — {$plan->billing_cycle->value}",
            'quantity' => 1,
            'unit_price' => $amount,
            'total' => $amount,
        ]);

        $this->accountingService->recordInvoiceIssued($invoice->fresh());

        $subscription->update([
            'next_billing_date' => $this->nextBillingDate(
                $subscription->next_billing_date,
                $plan->billing_cycle,
            ),
        ]);

        return [
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'total' => number_format($amount, 2, '.', ''),
        ];
    }

    protected function nextBillingDate(Carbon $current, BillingCycleEnum $cycle): Carbon
    {
        return match ($cycle) {
            BillingCycleEnum::YEARLY => $current->copy()->addYear(),
            BillingCycleEnum::MONTHLY => $current->copy()->addMonth(),
        };
    }
}
