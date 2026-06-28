<?php

namespace App\Repositories\Admin;

use App\Models\Payment;

class PaymentRepository extends TenantScopedRepository
{
    public function __construct(Payment $model)
    {
        $this->model = $model;
    }

    /**
     * @return array<string, string>
     */
    protected function deletionBlockRelations(): array
    {
        return [
            'journalEntries' => 'قيود محاسبية',
        ];
    }

    protected function deletionResourceLabel(): string
    {
        return 'الدفعة';
    }
}
