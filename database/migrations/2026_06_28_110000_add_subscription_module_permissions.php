<?php

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionTiers;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** @var list<string> */
    private const ACTIONS = ['create', 'read', 'update', 'delete'];

    /** @var array<string, list<string>> */
    private const GROUPS = [
        'customer' => [PermissionTiers::COMPANY_ADMIN],
        'subscription_plan' => [PermissionTiers::COMPANY_ADMIN],
        'subscription' => [PermissionTiers::COMPANY_ADMIN],
        'invoice' => [PermissionTiers::COMPANY_ADMIN],
        'payment' => [PermissionTiers::COMPANY_ADMIN],
        'account' => [PermissionTiers::COMPANY_ADMIN],
    ];

    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $created = collect();

        foreach (self::GROUPS as $category => $tiers) {
            foreach (self::ACTIONS as $action) {
                $permission = Permission::query()->firstOrCreate(
                    ['name' => "{$action}_{$category}", 'guard_name' => 'web'],
                    [
                        'group_category' => $category,
                        'group_user' => $tiers,
                    ],
                );

                if ($permission->wasRecentlyCreated === false) {
                    $permission->update(['group_user' => $tiers]);
                }

                $created->push($permission);
            }
        }

        $superAdmin = Role::query()->where('name', 'Super_Admin')->first();
        $companyAdmin = Role::query()->where('name', 'Company_Admin')->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo($created);
        }

        if ($companyAdmin) {
            $companyAdmin->givePermissionTo($created);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (array_keys(self::GROUPS) as $category) {
            foreach (self::ACTIONS as $action) {
                Permission::query()->where('name', "{$action}_{$category}")->delete();
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
