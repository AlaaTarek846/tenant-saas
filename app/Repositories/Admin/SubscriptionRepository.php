<?php

namespace App\Repositories\Admin;

use App\Models\Subscription;

class SubscriptionRepository extends TenantScopedRepository
{
    public function __construct(Subscription $model)
    {
        $this->model = $model;
    }

    /**
     * @return array<string, string>
     */
    protected function deletionBlockRelations(): array
    {
        return [
            'invoices' => 'فواتير',
        ];
    }

    protected function deletionResourceLabel(): string
    {
        return 'الاشتراك';
    }
}
