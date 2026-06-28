<?php

namespace App\Repositories\Admin;

use App\Models\Customer;

class CustomerRepository extends TenantScopedRepository
{
    public function __construct(Customer $model)
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
            'invoices' => 'فواتير',
        ];
    }

    protected function deletionResourceLabel(): string
    {
        return 'العميل';
    }
}
