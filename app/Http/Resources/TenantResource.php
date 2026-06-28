<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
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
            'country' => $this->country,
            'city' => $this->city,
            'phone' => $this->phone,
            'status' => $this->status?->value ?? $this->status,
            'logo_url' => $this->logoUrl(),
            'users_count' => $this->when(isset($this->users_count), $this->users_count),
            'customers_count' => $this->when(isset($this->customers_count), $this->customers_count),
            'active_subscriptions_count' => $this->when(
                isset($this->active_subscriptions_count),
                $this->active_subscriptions_count,
            ),
            'owner' => $this->when(
                $this->relationLoaded('owner') && $this->owner,
                fn () => [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                    'email' => $this->owner->email,
                ],
            ),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
