<?php

namespace App\Http\Requests\Admin;

use App\Enums\SubscriptionStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'next_billing_date' => ['required', 'date'],
            'status' => ['sometimes', Rule::enum(SubscriptionStatusEnum::class)],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ];
    }
}
