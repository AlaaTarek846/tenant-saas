<?php

namespace App\Services\Admin;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Admin\PermissionRepository;
use App\Repositories\Admin\RoleRepository;
use App\Services\BaseService;
use App\Support\TenantScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class RoleService extends BaseService
{
    public function __construct(
        RoleRepository $repository,
        protected PermissionRepository $permissionRepository,
    ) {
        $this->repository = $repository;
    }

    public function allOrPaginate($resource, $groupBy = null, ?User $actor = null)
    {
        $query = $this->repository->manageableQuery($actor);

        if ($search = request('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $query->latest('id');

        return allOrPaginate($query, $resource, $groupBy);
    }

    public function store(array $data, ?User $actor = null): Role
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        $data['guard_name'] = $data['guard_name'] ?? 'web';

        if (TenantScope::isScoped($actor)) {
            $data['tenant_id'] = TenantScope::id($actor);
        }

        /** @var Role $role */
        $role = $this->repository->store($data);

        if ($permissions !== []) {
            $this->repository->syncPermissions($role, $this->filterAssignablePermissions($permissions, $actor));
        }

        return $role;
    }

    public function show($id, ?User $actor = null): Role
    {
        $role = $this->repository->show($id);
        $this->ensureManageable($role, $actor);

        return $role->load('permissions');
    }

    public function update($id, array $data, ?User $actor = null): Role
    {
        $role = $this->repository->show($id);
        $this->ensureManageable($role, $actor);

        $permissions = $data['permissions'] ?? null;
        unset($data['permissions'], $data['tenant_id']);

        $role = $this->repository->update($id, $data);

        if (is_array($permissions)) {
            $this->repository->syncPermissions($role, $this->filterAssignablePermissions($permissions, $actor));
        }

        return $role->load('permissions');
    }

    public function destroy($id, ?User $actor = null): bool
    {
        $role = $this->repository->show($id);
        $this->ensureManageable($role, $actor);

        return (bool) $this->repository->destroy($id);
    }

    public function listPermissions(?User $actor = null)
    {
        return $this->permissionRepository
            ->assignablePermissionsQuery($actor)
            ->orderBy('group_category')
            ->orderBy('name')
            ->get();
    }

    public function listOptions(?User $actor = null)
    {
        return $this->repository
            ->assignableRolesQuery($actor)
            ->orderBy('name')
            ->get();
    }

    protected function ensureManageable(Role $role, ?User $actor = null): void
    {
        if ($this->repository->isSystemRole($role)) {
            throw ValidationException::withMessages([
                'role' => ['لا يمكن تعديل الأدوار النظامية.'],
            ]);
        }

        if (TenantScope::isScoped($actor) && ! $this->repository->belongsToTenant($role, TenantScope::id($actor))) {
            throw new AuthorizationException('ليس لديك صلاحية الوصول لهذا الدور.');
        }
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    protected function filterAssignablePermissions(array $permissions, ?User $actor): array
    {
        $allowed = $this->permissionRepository
            ->assignablePermissionsQuery($actor)
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->all();

        return array_values($allowed);
    }
}
