<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'guard_name',
        'group_user',
        'group_category',
        'tenant_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group_user' => 'array',
        ];
    }

    /**
     * @param  list<string>  $tiers
     */
    public function scopeForTiers($query, array $tiers)
    {
        return $query->where(function ($builder) use ($tiers) {
            foreach ($tiers as $tier) {
                $builder->orWhereJsonContains('group_user', $tier);
            }
        });
    }
}
