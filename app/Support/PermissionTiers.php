<?php

namespace App\Support;

class PermissionTiers
{
    public const SUPER_ADMIN = 'Super_Admin';

    public const COMPANY_ADMIN = 'Company_Admin';

    /**
     * @return list<string>
     */
    public static function forUser(?\App\Models\User $user): array
    {
        if ($user?->hasRole(self::SUPER_ADMIN)) {
            return [self::SUPER_ADMIN, self::COMPANY_ADMIN];
        }

        return [self::COMPANY_ADMIN];
    }

    public static function primaryForUser(?\App\Models\User $user): string
    {
        if ($user?->hasRole(self::SUPER_ADMIN)) {
            return self::SUPER_ADMIN;
        }

        return self::COMPANY_ADMIN;
    }
}
