<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'email_verified' => $this->hasVerifiedEmail(),
            'verify_code_expires_at' => $this->when(
                ! $this->hasVerifiedEmail(),
                $this->verify_code_expires_at,
            ),
            'verify_resend_available_in' => $this->when(
                ! $this->hasVerifiedEmail(),
                fn () => app(\App\Services\Auth\VerifyCodeService::class)->resendCooldownSeconds($this->resource),
            ),
            'status' => $this->status?->value ?? $this->status,
            'tenant_id' => $this->tenant_id,
            'is_owner' => (bool) $this->is_owner,
            'avatar_url' => $this->avatarUrl(),
            'company' => $this->when(
                $this->tenant_id && $this->relationLoaded('tenant') && $this->tenant,
                fn () => new TenantResource($this->tenant),
            ),
            'tenant_users_count' => $this->when(
                $this->relationLoaded('tenant') && $this->tenant && isset($this->tenant->users_count),
                fn () => $this->tenant->users_count,
            ),
            'roles' => $this->getRoleNames()->values()->all(),
            'permissions' => $this->getAllPermissions()->map(fn ($permission) => [
                'name' => $permission->name,
                'group_category' => $permission->group_category,
            ])->values()->all(),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
