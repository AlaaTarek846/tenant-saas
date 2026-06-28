<?php

namespace App\Support;

use App\Models\User;

class TenantScope
{
    public static function id(?User $user): ?int
    {
        return $user?->tenant_id;
    }

    public static function isScoped(?User $user): bool
    {
        return filled($user?->tenant_id);
    }
}
