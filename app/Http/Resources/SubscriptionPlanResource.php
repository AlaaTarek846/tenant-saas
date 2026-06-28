<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'billing_cycle' => $this->billing_cycle?->value ?? $this->billing_cycle,
            'currency' => $this->currency,
            'status' => $this->status?->value ?? $this->status,
            'features' => SubscriptionPlanFeatureResource::collection($this->whenLoaded('features')),
            'features_count' => $this->when(isset($this->features_count), $this->features_count),
            'subscriptions_count' => $this->when(isset($this->subscriptions_count), $this->subscriptions_count),
            'active_subscriptions_count' => $this->when(
                isset($this->active_subscriptions_count),
                $this->active_subscriptions_count,
            ),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
