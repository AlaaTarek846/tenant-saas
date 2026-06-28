<?php

namespace App\Services\Admin\Concerns;

use App\Models\User;
use App\Support\AdminPermissions;
use App\Support\TenantScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

trait HandlesTenantScopedAdmin
{
    protected function scopedQuery(?User $actor)
    {
        $this->ensureAdminAccess($actor);

        $tenantId = TenantScope::isScoped($actor) ? TenantScope::id($actor) : null;

        return $this->repository->scopedQuery($tenantId);
    }

    protected function ensureAdminAccess(?User $actor): void
    {
        if ($actor?->hasAnyRole(['Super_Admin', 'Company_Admin'])) {
            return;
        }

        if ($actor?->hasAnyPermission(AdminPermissions::COMPANY)) {
            return;
        }

        throw new AuthorizationException('ليس لديك صلاحية الوصول.');
    }

    protected function ensureCanAccess(Model $record, ?User $actor, string $message = 'ليس لديك صلاحية الوصول لهذا السجل.'): void
    {
        if (! TenantScope::isScoped($actor)) {
            return;
        }

        if ((int) $record->tenant_id === TenantScope::id($actor)) {
            return;
        }

        throw new AuthorizationException($message);
    }

    protected function applyTenantId(array $data, ?User $actor): array
    {
        if (TenantScope::isScoped($actor)) {
            $data['tenant_id'] = TenantScope::id($actor);
        }

        return $data;
    }
}
