<?php

namespace App\Repositories\Admin;

use App\Models\Account;

class AccountRepository extends TenantScopedRepository
{
    public function __construct(Account $model)
    {
        $this->model = $model;
    }

    public function findByCode(int $tenantId, string $code): Account
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->firstOrFail();
    }

    /**
     * @return array<string, string>
     */
    protected function deletionBlockRelations(): array
    {
        return [
            'journalEntryDetails' => 'بنود قيود محاسبية',
        ];
    }

    protected function deletionResourceLabel(): string
    {
        return 'الحساب';
    }
}
