<?php

namespace App\Http\Requests\Admin;

use App\Enums\BillingCycleEnum;
use App\Enums\SubscriptionPlanStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionPlanRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', Rule::enum(BillingCycleEnum::class)],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', Rule::enum(SubscriptionPlanStatusEnum::class)],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'features' => ['sometimes', 'array'],
            'features.*.feature' => ['required_with:features', 'string', 'max:255'],
            'features.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }
}
