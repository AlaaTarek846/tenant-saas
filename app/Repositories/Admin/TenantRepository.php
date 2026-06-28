<?php

namespace App\Repositories\Admin;

use App\Models\Tenant;
use App\Enums\SubscriptionStatusEnum;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

class TenantRepository extends BaseRepository
{
    public function __construct(Tenant $model)
    {
        $this->model = $model;
    }

    public function listQuery(): Builder
    {
        return $this->model->newQuery()
            ->with(['owner'])
            ->withCount([
                'users',
                'customers',
                'subscriptions as active_subscriptions_count' => fn ($query) => $query
                    ->where('status', SubscriptionStatusEnum::ACTIVE),
            ])
            ->latest('id');
    }
}
