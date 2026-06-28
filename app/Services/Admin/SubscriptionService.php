<?php

namespace App\Services\Admin;

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Repositories\Admin\SubscriptionRepository;
use App\Services\Admin\Concerns\HandlesTenantScopedAdmin;
use App\Services\BaseService;
use App\Support\TenantScope;
use Illuminate\Validation\ValidationException;

class SubscriptionService extends BaseService
{
    use HandlesTenantScopedAdmin;

    public function __construct(SubscriptionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function allOrPaginate($resource, $groupBy = null, ?User $actor = null)
    {
        $query = $this->scopedQuery($actor)
            ->with(['customer', 'subscriptionPlan'])
            ->withCount('invoices');

        if ($search = request('search')) {
            $query->whereHas('customer', fn ($builder) => $builder->where('name', 'like', "%{$search}%"));
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($startDate = request('start_date')) {
            $query->whereDate('start_date', $startDate);
        }

        if ($nextBillingDate = request('next_billing_date')) {
            $query->whereDate('next_billing_date', $nextBillingDate);
        }

        $query->latest('id');

        return allOrPaginate($query, $resource, $groupBy);
    }

    public function options(?User $actor = null)
    {
        return $this->scopedQuery($actor)
            ->with(['customer:id,name', 'subscriptionPlan:id,name'])
            ->select(['id', 'customer_id', 'subscription_plan_id', 'status'])
            ->latest('id')
            ->get();
    }

    public function store(array $data, ?User $actor = null): Subscription
    {
        $data = $this->applyTenantId($data, $actor);
        $this->ensureRelatedModelsBelongToTenant($data, $actor);

        return $this->repository->store($data);
    }

    public function show($id, ?User $actor = null): Subscription
    {
        $subscription = $this->repository->show($id);
        $this->ensureCanAccess($subscription, $actor, 'ليس لديك صلاحية الوصول لهذا الاشتراك.');

        return $subscription->load(['customer', 'subscriptionPlan']);
    }

    public function update($id, array $data, ?User $actor = null): Subscription
    {
        $subscription = $this->repository->show($id);
        $this->ensureCanAccess($subscription, $actor, 'ليس لديك صلاحية تعديل هذا الاشتراك.');
        unset($data['tenant_id']);

        if (isset($data['customer_id']) || isset($data['subscription_plan_id'])) {
            $this->ensureRelatedModelsBelongToTenant([
                'tenant_id' => $subscription->tenant_id,
                'customer_id' => $data['customer_id'] ?? $subscription->customer_id,
                'subscription_plan_id' => $data['subscription_plan_id'] ?? $subscription->subscription_plan_id,
            ], $actor);
        }

        return $this->repository->update($id, $data);
    }

    public function destroy($id, ?User $actor = null): bool
    {
        $subscription = $this->repository->show($id);
        $this->ensureCanAccess($subscription, $actor, 'ليس لديك صلاحية حذف هذا الاشتراك.');

        return (bool) $this->repository->destroy($id);
    }

    protected function ensureRelatedModelsBelongToTenant(array $data, ?User $actor): void
    {
        $tenantId = $data['tenant_id'] ?? TenantScope::id($actor);

        $customer = Customer::query()->find($data['customer_id'] ?? null);
        $plan = SubscriptionPlan::query()->find($data['subscription_plan_id'] ?? null);

        if (! $customer || (int) $customer->tenant_id !== (int) $tenantId) {
            throw ValidationException::withMessages([
                'customer_id' => ['العميل غير صالح لهذه الشركة.'],
            ]);
        }

        if (! $plan || (int) $plan->tenant_id !== (int) $tenantId) {
            throw ValidationException::withMessages([
                'subscription_plan_id' => ['خطة الاشتراك غير صالحة لهذه الشركة.'],
            ]);
        }
    }
}
