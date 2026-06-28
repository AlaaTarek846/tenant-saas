<?php

namespace App\Services\Admin;

use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function show(User $user): array
    {
        $user->load('tenant');

        $payload = [
            'user' => new UserResource($user),
            'can_manage_company' => $user->hasRole('Company_Admin') && $user->tenant_id !== null,
        ];

        if ($payload['can_manage_company']) {
            $payload['company'] = new TenantResource($user->tenant);
        }

        return $payload;
    }

    public function updateUser(User $user, array $data): User
    {
        if (isset($data['password']) && filled($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($requestFile = request()->file('avatar')) {
            $user->setSingleMedia('avatar', $requestFile, 'avatars');
        }

        unset($data['avatar']);

        $user->update($data);

        return $user->fresh()->load('tenant');
    }

    public function updateCompany(User $user, array $data)
    {
        if (! $user->hasRole('Company_Admin') || ! $user->tenant_id) {
            throw new AuthorizationException('ليس لديك صلاحية تعديل بيانات الشركة.');
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            throw ValidationException::withMessages([
                'company' => ['لا توجد شركة مرتبطة بحسابك.'],
            ]);
        }

        if ($requestFile = request()->file('logo')) {
            $tenant->setSingleMedia('logo', $requestFile, 'company-logos');
        }

        unset($data['logo']);

        $tenant->update($data);

        return $tenant->fresh();
    }
}
