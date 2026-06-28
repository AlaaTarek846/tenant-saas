<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'customer_id' => $this->customer_id,
            'subscription_plan_id' => $this->subscription_plan_id,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'next_billing_date' => $this->next_billing_date?->format('Y-m-d'),
            'status' => $this->status?->value ?? $this->status,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'subscription_plan' => new SubscriptionPlanResource($this->whenLoaded('subscriptionPlan')),
            'invoices_count' => $this->when(isset($this->invoices_count), $this->invoices_count),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
