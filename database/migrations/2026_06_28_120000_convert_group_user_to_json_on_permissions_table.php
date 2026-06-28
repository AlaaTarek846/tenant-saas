<?php

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionTiers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = DB::table('permissions')->get(['id', 'group_user']);

        foreach ($permissions as $permission) {
            if ($permission->group_user === null) {
                continue;
            }

            $decoded = json_decode($permission->group_user, true);

            if (! is_array($decoded)) {
                DB::table('permissions')
                    ->where('id', $permission->id)
                    ->update(['group_user' => json_encode([$permission->group_user])]);
            }
        }

        DB::table('permissions')
            ->where('group_category', 'role')
            ->update(['group_user' => json_encode([
                PermissionTiers::SUPER_ADMIN,
                PermissionTiers::COMPANY_ADMIN,
            ])]);

        DB::statement('DROP INDEX IF EXISTS permissions_group_user_index');

        DB::statement('ALTER TABLE permissions ALTER COLUMN group_user TYPE jsonb USING group_user::jsonb');

        $this->syncSystemRolePermissions();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        DB::statement('ALTER TABLE permissions ALTER COLUMN group_user TYPE varchar(255) USING group_user::text');

        Schema::table('permissions', function (Blueprint $table) {
            $table->index('group_user');
        });

        $permissions = DB::table('permissions')->get(['id', 'group_user', 'group_category']);

        foreach ($permissions as $permission) {
            if ($permission->group_user === null) {
                continue;
            }

            $tiers = json_decode($permission->group_user, true);
            $value = is_array($tiers) ? ($tiers[0] ?? null) : $permission->group_user;

            if ($permission->group_category === 'role') {
                $value = PermissionTiers::SUPER_ADMIN;
            }

            DB::table('permissions')
                ->where('id', $permission->id)
                ->update(['group_user' => $value]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    protected function syncSystemRolePermissions(): void
    {
        $superAdmin = Role::query()->where('name', PermissionTiers::SUPER_ADMIN)->first();
        $companyAdmin = Role::query()->where('name', PermissionTiers::COMPANY_ADMIN)->first();

        $allPermissions = Permission::query()->get();

        $companyPermissionNames = $allPermissions
            ->filter(fn (Permission $permission) => in_array(
                PermissionTiers::COMPANY_ADMIN,
                $permission->group_user ?? [],
                true,
            ))
            ->pluck('name');

        $superPermissionNames = $allPermissions
            ->filter(fn (Permission $permission) => in_array(
                PermissionTiers::SUPER_ADMIN,
                $permission->group_user ?? [],
                true,
            ))
            ->pluck('name');

        if ($companyAdmin) {
            $companyAdmin->syncPermissions($companyPermissionNames);
        }

        if ($superAdmin) {
            $superAdmin->syncPermissions($superPermissionNames->merge($companyPermissionNames)->unique()->values());
        }
    }
};
