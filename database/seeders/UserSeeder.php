<?php

namespace Database\Seeders;

use App\Enums\UserStatusEnum;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Admin\ChartOfAccountsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->truncate();
        DB::table('password_reset_tokens')->truncate();
        DB::table('sessions')->truncate();
        Tenant::query()->delete();

     

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('123456'),
            'status' => UserStatusEnum::ACTIVE,
            'tenant_id' => null,
            'verify_code' => 123456,
            'verify_code_expires_at' => now()->addMinutes(1),
            
        ]);
        $superAdmin->assignRole('Super_Admin');
        $tenant = Tenant::create([
            'name' => 'Company 1',
            'email' => 'company1@gmail.com',
            'country' => 'Egypt',
            'city' => 'Cairo',
            'phone' => '01010101010',
            'status' => UserStatusEnum::ACTIVE,
        ]);
        $companyAdmin = User::create([
            'name' => 'Company Admin',
            'email' => 'companyadmin@gmail.com',
            'password' => Hash::make('123456'),
            'status' => UserStatusEnum::ACTIVE,
            'tenant_id' => $tenant->id,
            'is_owner' => true,
            'verify_code' => 123456,
            'verify_code_expires_at' => now()->addMinutes(1),
        ]);
        $companyAdmin->assignRole('Company_Admin');

        app(ChartOfAccountsService::class)->seedForTenant($tenant);

        Role::create([
            'name' => 'Employee',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);
    }
}
