<?php

namespace App\Repositories\Admin;

use App\Models\Role;
use App\Models\User;
use App\Repositories\BaseRepository;
use App\Support\TenantScope;
use Illuminate\Database\Eloquent\Builder;

class RoleRepository extends BaseRepository
{
    /** @var list<string> */
    public const SYSTEM_ROLES = ['Super_Admin', 'Company_Admin'];

    public function __construct(Role $model)
    {
        $this->model = $model;
    }

    public function manageableQuery(?User $actor = null): Builder
    {
        return $this->assignableRolesQuery($actor)->withCount('permissions');
    }

    public function assignableRolesQuery(?User $actor = null): Builder
    {
        $query = $this->model->newQuery()
            ->whereNotIn('name', self::SYSTEM_ROLES);

        if (TenantScope::isScoped($actor)) {
            $query->where('tenant_id', TenantScope::id($actor));
        }

        return $query;
    }

    public function tenantRolesQuery(?int $tenantId): Builder
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereNotIn('name', self::SYSTEM_ROLES);
    }

    public function isSystemRole(Role $role): bool
    {
        return in_array($role->name, self::SYSTEM_ROLES, true);
    }

    public function belongsToTenant(Role $role, ?int $tenantId): bool
    {
        return $role->tenant_id !== null && $role->tenant_id === $tenantId;
    }

    public function syncPermissions(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);

        return $role->load('permissions');
    }

    /**
     * @return array<string, string>
     */
    protected function deletionBlockRelations(): array
    {
        return [
            'users' => 'مستخدمين',
        ];
    }

    protected function deletionResourceLabel(): string
    {
        return 'الدور';
    }
}
