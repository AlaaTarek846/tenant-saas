<?php

namespace App\Services\Admin;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Enums\SubscriptionStatusEnum;
use App\Repositories\Admin\SubscriptionPlanRepository;
use App\Services\Admin\Concerns\HandlesTenantScopedAdmin;
use App\Services\BaseService;
use App\Support\TenantScope;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanService extends BaseService
{
    use HandlesTenantScopedAdmin;

    public function __construct(SubscriptionPlanRepository $repository)
    {
        $this->repository = $repository;
    }

    public function allOrPaginate($resource, $groupBy = null, ?User $actor = null)
    {
        $query = $this->scopedQuery($actor)->withCount([
            'features',
            'subscriptions',
            'subscriptions as active_subscriptions_count' => fn ($builder) => $builder
                ->where('status', SubscriptionStatusEnum::ACTIVE),
        ]);

        if ($search = request('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $query->latest('id');

        $result = allOrPaginate($query, $resource, $groupBy);
        $result['paginate']['subscribed_customers_count'] = $this->countSubscribedCustomers($actor);

        return $result;
    }

    public function countSubscribedCustomers(?User $actor): int
    {
        $query = Subscription::query();

        if (TenantScope::isScoped($actor)) {
            $query->where('tenant_id', TenantScope::id($actor));
        }

        return (int) $query
            ->where('status', SubscriptionStatusEnum::ACTIVE)
            ->distinct()
            ->count('customer_id');
    }

    public function options(?User $actor = null)
    {
        return $this->scopedQuery($actor)
            ->select(['id', 'name', 'price', 'currency', 'billing_cycle'])
            ->orderBy('name')
            ->get();
    }

    public function store(array $data, ?User $actor = null): SubscriptionPlan
    {
        $features = $data['features'] ?? [];
        unset($data['features']);
        $data = $this->applyTenantId($data, $actor);

        return DB::transaction(function () use ($data, $features) {
            /** @var SubscriptionPlan $plan */
            $plan = $this->repository->store($data);
            $this->syncFeatures($plan, $features);

            return $plan->load('features');
        });
    }

    public function show($id, ?User $actor = null): SubscriptionPlan
    {
        $plan = $this->repository->show($id);
        $this->ensureCanAccess($plan, $actor, 'ليس لديك صلاحية الوصول لهذه الخطة.');

        return $plan->load(['features'])->loadCount('subscriptions');
    }

    public function update($id, array $data, ?User $actor = null): SubscriptionPlan
    {
        $plan = $this->repository->show($id);
        $this->ensureCanAccess($plan, $actor, 'ليس لديك صلاحية تعديل هذه الخطة.');

        $features = $data['features'] ?? null;
        unset($data['features'], $data['tenant_id']);

        return DB::transaction(function () use ($id, $data, $features, $plan) {
            $plan = $this->repository->update($id, $data);

            if (is_array($features)) {
                $this->syncFeatures($plan, $features);
            }

            return $plan->load('features');
        });
    }

    public function destroy($id, ?User $actor = null): bool
    {
        $plan = $this->repository->show($id);
        $this->ensureCanAccess($plan, $actor, 'ليس لديك صلاحية حذف هذه الخطة.');

        return (bool) $this->repository->destroy($id);
    }

    /**
     * @param  list<array{feature: string, value?: string|null}>  $features
     */
    protected function syncFeatures(SubscriptionPlan $plan, array $features): void
    {
        $plan->features()->delete();

        foreach ($features as $feature) {
            if (blank($feature['feature'] ?? null)) {
                continue;
            }

            $plan->features()->create([
                'feature' => $feature['feature'],
                'value' => $feature['value'] ?? null,
            ]);
        }
    }
}
