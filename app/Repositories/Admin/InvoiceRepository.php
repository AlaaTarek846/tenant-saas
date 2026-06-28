<?php

namespace App\Repositories\Admin;

use App\Models\Invoice;

class InvoiceRepository extends TenantScopedRepository
{
    public function __construct(Invoice $model)
    {
        $this->model = $model;
    }

    public function nextNumber(int $tenantId): string
    {
        $count = $this->model->newQuery()->where('tenant_id', $tenantId)->count() + 1;

        return sprintf('INV-%d-%05d', $tenantId, $count);
    }

    /**
     * @return array<string, string>
     */
    protected function deletionBlockRelations(): array
    {
        return [
            'payments' => 'مدفوعات',
            'journalEntries' => 'قيود محاسبية',
        ];
    }

    protected function deletionResourceLabel(): string
    {
        return 'الفاتورة';
    }
}
