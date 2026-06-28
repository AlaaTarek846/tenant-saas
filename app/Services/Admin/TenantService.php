<?php

namespace App\Services\Admin;

use App\Enums\UserStatusEnum;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Admin\TenantRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantService extends BaseService
{
    public function __construct(TenantRepository $repository)
    {
        $this->repository = $repository;
    }

    public function allOrPaginate($resource, $groupBy = null)
    {
        $query = $this->repository->listQuery();

        if ($search = request('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        return allOrPaginate($query, $resource, $groupBy);
    }

    public function show($id): Tenant
    {
        return $this->repository->listQuery()->findOrFail($id);
    }

    public function forceDestroy(int $id): bool
    {
        $tenant = $this->repository->show($id);

        return DB::transaction(function () use ($tenant) {
            $users = User::query()->where('tenant_id', $tenant->id)->get();

            foreach ($users as $user) {
                if (method_exists($user, 'cleanMedia')) {
                    $user->cleanMedia();
                }

                $user->tokens()->delete();
                $this->invalidateUserSessions($user);
                $user->delete();
            }

            $this->purgeTenantRoles($tenant);

            if (method_exists($tenant, 'cleanMedia')) {
                $tenant->cleanMedia();
            }

            return (bool) $tenant->delete();
        });
    }

    public function suspend(int $id): Tenant
    {
        $tenant = $this->repository->show($id);

        if ($tenant->status === UserStatusEnum::SUSPENDED) {
            throw ValidationException::withMessages([
                'tenant' => ['الشركة موقوفة بالفعل.'],
            ]);
        }

        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => UserStatusEnum::SUSPENDED]);

            $users = User::query()->where('tenant_id', $tenant->id)->get();

            foreach ($users as $user) {
                $this->suspendUser($user);
            }

            return $tenant->fresh()->load(['owner'])->loadCount('users');
        });
    }

    public function suspendUser(User $user): User
    {
        $user->forceFill([
            'status' => UserStatusEnum::SUSPENDED,
            'email_verified_at' => null,
            'verify_code' => generateVerifyCode(),
            'verify_code_expires_at' => now()->addMinutes(config('verify.expires_minutes', 1)),
        ])->save();

        $this->invalidateUserSessions($user);

        return $user->fresh();
    }

    protected function purgeTenantRoles(Tenant $tenant): void
    {
        Role::query()->where('tenant_id', $tenant->id)->delete();
    }

    protected function invalidateUserSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
