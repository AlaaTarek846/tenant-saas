<?php

namespace App\Services\Admin;

use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Enums\UserStatusEnum;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\TenantResource;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantScope;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function __construct(
        protected ReportService $reportService,
    ) {}

    public function getData(User $user): array
    {
        $tenantId = TenantScope::isScoped($user) ? TenantScope::id($user) : null;
        $platformView = $this->isPlatformView($tenantId, $user);

        $data = [
            'stats' => $this->stats($tenantId, $user),
            'recent_users' => $this->recentUsers($tenantId),
        ];

        if ($platformView) {
            $data['recent_tenants'] = $this->recentTenants();
            $data['recent_customers'] = [];
            $data['recent_invoices'] = [];
            $data['recent_payments'] = [];
        } else {
            $data['recent_tenants'] = [];
            $data['recent_customers'] = CustomerResource::collection(
                $this->scoped(Customer::query(), $tenantId)->latest('id')->limit(5)->get()
            )->resolve();
            $data['recent_invoices'] = InvoiceResource::collection(
                $this->scoped(Invoice::query(), $tenantId)->with('customer')->latest('id')->limit(5)->get()
            )->resolve();
            $data['recent_payments'] = PaymentResource::collection(
                $this->scoped(Payment::query(), $tenantId)->with('invoice.customer')->latest('id')->limit(5)->get()
            )->resolve();
        }

        return $data;
    }

    protected function stats(?int $tenantId, User $user): array
    {
        $usersQuery = User::query();
        $this->scoped($usersQuery, $tenantId);

        $invoiceQuery = $this->scoped(Invoice::query(), $tenantId);
        $paymentQuery = $this->scoped(Payment::query(), $tenantId);

        $stats = [
            'users_count' => (clone $usersQuery)->count(),
            'customers_count' => $this->scoped(Customer::query(), $tenantId)->count(),
            'subscription_plans_count' => $this->scoped(SubscriptionPlan::query(), $tenantId)->count(),
            'subscriptions_count' => $this->scoped(Subscription::query(), $tenantId)->count(),
            'active_subscriptions_count' => $this->scoped(Subscription::query(), $tenantId)
                ->where('status', SubscriptionStatusEnum::ACTIVE)->count(),
            'invoices_count' => (clone $invoiceQuery)->count(),
            'pending_invoices_count' => (clone $invoiceQuery)
                ->where('status', InvoiceStatusEnum::PENDING)->count(),
            'paid_invoices_count' => (clone $invoiceQuery)
                ->where('status', InvoiceStatusEnum::PAID)->count(),
            'invoices_total' => (float) (clone $invoiceQuery)->sum('total'),
            'pending_invoices_total' => (float) (clone $invoiceQuery)
                ->where('status', InvoiceStatusEnum::PENDING)->sum('total'),
            'payments_count' => (clone $paymentQuery)->count(),
            'payments_total' => (float) (clone $paymentQuery)
                ->where('status', PaymentStatusEnum::PAID)->sum('amount'),
            'journal_entries_count' => $tenantId !== null
                ? JournalEntry::query()->where('tenant_id', $tenantId)->count()
                : JournalEntry::query()->count(),
            'accounts_count' => $this->scoped(Account::query(), $tenantId)->count(),
            'tenants_count' => $tenantId === null ? Tenant::query()->count() : null,
            'active_tenants_count' => $tenantId === null
                ? Tenant::query()->where('status', UserStatusEnum::ACTIVE)->count()
                : null,
            'is_super_admin' => $user->hasRole('Super_Admin'),
            'is_company_admin' => $user->hasRole('Company_Admin'),
            'is_platform_view' => $this->isPlatformView($tenantId, $user),
        ];

        if ($tenantId !== null) {
            $stats = array_merge($stats, $this->financialSnapshot($user));
        }

        return $stats;
    }

    protected function financialSnapshot(User $user): array
    {
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth();
        $asOf = Carbon::now();

        $income = $this->reportService->incomeStatement($user, $from, $to);
        $balance = $this->reportService->balanceSheet($user, $asOf);

        return [
            'cash_balance' => (float) ($balance['highlight']['cash'] ?? 0),
            'accounts_receivable' => (float) ($balance['highlight']['accounts_receivable'] ?? 0),
            'deferred_revenue' => (float) ($balance['highlight']['deferred_revenue'] ?? 0),
            'subscription_revenue_mtd' => (float) ($income['subscription_revenue']['amount'] ?? 0),
            'total_assets' => (float) ($balance['totals']['assets'] ?? 0),
            'total_liabilities' => (float) ($balance['totals']['liabilities'] ?? 0),
        ];
    }

    protected function recentUsers(?int $tenantId)
    {
        $query = User::query()->with('roles');
        $this->scoped($query, $tenantId);

        return $query->latest('id')->limit(5)->get()
            ->map(fn (User $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'status' => $item->status?->value ?? $item->status,
                'roles' => $item->getRoleNames()->values()->all(),
            ])
            ->all();
    }

    protected function recentTenants(): array
    {
        return TenantResource::collection(
            Tenant::query()
                ->with('owner')
                ->withCount([
                    'users',
                    'customers',
                    'subscriptions as active_subscriptions_count' => fn ($query) => $query
                        ->where('status', SubscriptionStatusEnum::ACTIVE),
                ])
                ->latest('id')
                ->limit(5)
                ->get()
        )->resolve();
    }

    protected function isPlatformView(?int $tenantId, User $user): bool
    {
        return $tenantId === null && $user->hasRole('Super_Admin');
    }

    protected function scoped($query, ?int $tenantId)
    {
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }
}
