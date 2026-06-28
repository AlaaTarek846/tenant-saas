<?php

namespace App\Services\Admin;

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\User;
use App\Enums\SubscriptionStatusEnum;
use App\Repositories\Admin\CustomerRepository;
use App\Services\Admin\Concerns\HandlesTenantScopedAdmin;
use App\Services\BaseService;
use App\Support\TenantScope;

class CustomerService extends BaseService
{
    use HandlesTenantScopedAdmin;

    public function __construct(CustomerRepository $repository)
    {
        $this->repository = $repository;
    }

    public function allOrPaginate($resource, $groupBy = null, ?User $actor = null)
    {
        $query = $this->scopedQuery($actor)->withCount([
            'subscriptions',
            'subscriptions as active_subscriptions_count' => fn ($builder) => $builder
                ->where('status', SubscriptionStatusEnum::ACTIVE),
        ]);

        if ($search = request('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $query->latest('id');

        $result = allOrPaginate($query, $resource, $groupBy);
        $result['paginate']['active_subscribers_count'] = $this->countActiveSubscribers($actor);

        return $result;
    }

    public function countActiveSubscribers(?User $actor): int
    {
        $query = Subscription::query()->where('status', SubscriptionStatusEnum::ACTIVE);

        if (TenantScope::isScoped($actor)) {
            $query->where('tenant_id', TenantScope::id($actor));
        }

        return (int) $query->distinct()->count('customer_id');
    }

    public function options(?User $actor = null)
    {
        return $this->scopedQuery($actor)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    public function store(array $data, ?User $actor = null): Customer
    {
        $data = $this->applyTenantId($data, $actor);

        return $this->repository->store($data);
    }

    public function show($id, ?User $actor = null): Customer
    {
        $customer = $this->repository->show($id);
        $this->ensureCanAccess($customer, $actor, 'ليس لديك صلاحية الوصول لهذا العميل.');

        return $customer->loadCount('subscriptions');
    }

    public function update($id, array $data, ?User $actor = null): Customer
    {
        $customer = $this->repository->show($id);
        $this->ensureCanAccess($customer, $actor, 'ليس لديك صلاحية تعديل هذا العميل.');
        unset($data['tenant_id']);

        return $this->repository->update($id, $data);
    }

    public function destroy($id, ?User $actor = null): bool
    {
        $customer = $this->repository->show($id);
        $this->ensureCanAccess($customer, $actor, 'ليس لديك صلاحية حذف هذا العميل.');

        return (bool) $this->repository->destroy($id);
    }
}
