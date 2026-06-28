<?php

namespace App\Repositories\Admin;

use App\Models\Permission;
use App\Models\User;
use App\Repositories\BaseRepository;
use App\Support\PermissionTiers;
use Illuminate\Database\Eloquent\Builder;

class PermissionRepository extends BaseRepository
{
    public function __construct(Permission $model)
    {
        $this->model = $model;
    }

    public function forTierQuery(string $tier): Builder
    {
        return $this->model->newQuery()->whereJsonContains('group_user', $tier);
    }

    /**
     * @param  list<string>  $tiers
     */
    public function forTiersQuery(array $tiers): Builder
    {
        return $this->model->newQuery()->forTiers($tiers);
    }

    public function assignablePermissionsQuery(?User $actor = null): Builder
    {
        return $this->forTiersQuery(PermissionTiers::forUser($actor));
    }

    public function companyPermissionsQuery(): Builder
    {
        return $this->forTierQuery(PermissionTiers::COMPANY_ADMIN);
    }
}
