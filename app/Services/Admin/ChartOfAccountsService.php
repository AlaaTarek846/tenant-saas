<?php

namespace App\Services\Admin;

use App\Models\Account;
use App\Models\Tenant;
use App\Repositories\Admin\AccountRepository;
use App\Support\ChartOfAccounts;

class ChartOfAccountsService
{
    public function __construct(
        protected AccountRepository $accountRepository,
    ) {}

    public function seedForTenant(Tenant|int $tenant): void
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        foreach (ChartOfAccounts::defaults() as $definition) {
            Account::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'code' => $definition['code'],
                ],
                [
                    'name' => $definition['name'],
                    'type' => $definition['type'],
                ],
            );
        }
    }

    public function seedForAllTenants(): void
    {
        Tenant::query()->each(fn (Tenant $tenant) => $this->seedForTenant($tenant));
    }
}
