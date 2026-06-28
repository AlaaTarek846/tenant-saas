<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Repositories\Admin\AccountRepository;
use App\Services\Admin\Concerns\HandlesTenantScopedAdmin;
use App\Services\BaseService;

class AccountService extends BaseService
{
    use HandlesTenantScopedAdmin;

    public function __construct(AccountRepository $repository)
    {
        $this->repository = $repository;
    }

    public function allOrPaginate($resource, $groupBy = null, ?User $actor = null)
    {
        $query = $this->scopedQuery($actor);

        if ($search = request('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($type = request('type')) {
            $query->where('type', $type);
        }

        $query->orderBy('code');

        return allOrPaginate($query, $resource, $groupBy);
    }

    public function store(array $data, ?User $actor = null)
    {
        $data = $this->applyTenantId($data, $actor);

        return $this->repository->store($data);
    }

    public function show($id, ?User $actor = null)
    {
        $account = $this->repository->show($id);
        $this->ensureCanAccess($account, $actor, 'ليس لديك صلاحية الوصول لهذا الحساب.');

        return $account;
    }

    public function update($id, array $data, ?User $actor = null)
    {
        $account = $this->repository->show($id);
        $this->ensureCanAccess($account, $actor, 'ليس لديك صلاحية تعديل هذا الحساب.');
        unset($data['tenant_id']);

        return $this->repository->update($id, $data);
    }

    public function destroy($id, ?User $actor = null): bool
    {
        $account = $this->repository->show($id);
        $this->ensureCanAccess($account, $actor, 'ليس لديك صلاحية حذف هذا الحساب.');

        return (bool) $this->repository->destroy($id);
    }
}
