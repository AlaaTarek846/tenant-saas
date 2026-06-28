<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionTiers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /** @var list<string> */
    private const ACTIONS = ['create', 'read', 'update', 'delete'];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('role_has_permissions')->truncate();

        $superAdmin = Role::create(['name' => 'Super_Admin', 'guard_name' => 'web']);
        $companyAdmin = Role::create(['name' => 'Company_Admin', 'guard_name' => 'web']);

        $permissionGroups = [
            'user' => [
                'group_user' => [PermissionTiers::COMPANY_ADMIN],
            ],
            'role' => [
                'group_user' => [PermissionTiers::SUPER_ADMIN, PermissionTiers::COMPANY_ADMIN],
            ],
            'tenant' => [
                'group_user' => [PermissionTiers::SUPER_ADMIN],
            ],
            'customer' => [
                'group_user' => [PermissionTiers::COMPANY_ADMIN],
            ],
            'subscription_plan' => [
                'group_user' => [PermissionTiers::COMPANY_ADMIN],
            ],
            'subscription' => [
                'group_user' => [PermissionTiers::COMPANY_ADMIN],
            ],
            'invoice' => [
                'group_user' => [PermissionTiers::COMPANY_ADMIN],
            ],
            'payment' => [
                'group_user' => [PermissionTiers::COMPANY_ADMIN],
            ],
            'account' => [
                'group_user' => [PermissionTiers::COMPANY_ADMIN],
            ],
        ];

        $companyPermissions = collect();
        $superAdminPermissions = collect();

        foreach ($permissionGroups as $category => $config) {
            $tiers = $config['group_user'];

            foreach (self::ACTIONS as $action) {
                $permission = Permission::create([
                    'name' => "{$action}_{$category}",
                    'guard_name' => 'web',
                    'group_category' => $category,
                    'group_user' => $tiers,
                ]);

                if (in_array(PermissionTiers::COMPANY_ADMIN, $tiers, true)) {
                    $companyPermissions->push($permission);
                }

                if (in_array(PermissionTiers::SUPER_ADMIN, $tiers, true)) {
                    $superAdminPermissions->push($permission);
                }
            }
        }

        $superAdmin->givePermissionTo($superAdminPermissions);
        $superAdmin->givePermissionTo($companyPermissions);
        $companyAdmin->givePermissionTo($companyPermissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->syncSystemUserRoles();
    }

    protected function syncSystemUserRoles(): void
    {
        User::query()->where('email', 'superadmin@gmail.com')->first()?->syncRoles(['Super_Admin']);
        User::query()->where('email', 'companyadmin@gmail.com')->first()?->syncRoles(['Company_Admin']);
    }
}
