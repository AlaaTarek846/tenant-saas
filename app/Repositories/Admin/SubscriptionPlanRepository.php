<?php

namespace App\Repositories\Admin;

use App\Models\SubscriptionPlan;

class SubscriptionPlanRepository extends TenantScopedRepository
{
    public function __construct(SubscriptionPlan $model)
    {
        $this->model = $model;
    }

    /**
     * @return array<string, string>
     */
    protected function deletionBlockRelations(): array
    {
        return [
            'subscriptions' => 'اشتراكات',
            'invoiceItems' => 'بنود فواتير',
        ];
    }

    protected function deletionResourceLabel(): string
    {
        return 'خطة الاشتراك';
    }
}
