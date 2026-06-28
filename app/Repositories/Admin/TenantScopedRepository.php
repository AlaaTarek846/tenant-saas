<?php

namespace App\Repositories\Admin;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

abstract class TenantScopedRepository extends BaseRepository
{
    public function scopedQuery(?int $tenantId = null): Builder
    {
        $query = $this->model->newQuery();

        if ($tenantId !== null) {
            $query->where($this->model->getTable().'.tenant_id', $tenantId);
        }

        return $query;
    }
}
