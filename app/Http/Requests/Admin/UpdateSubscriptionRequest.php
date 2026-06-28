<?php

namespace App\Http\Requests\Admin;

use App\Enums\SubscriptionStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
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
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'subscription_plan_id' => ['sometimes', 'integer', 'exists:subscription_plans,id'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
            'next_billing_date' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::enum(SubscriptionStatusEnum::class)],
        ];
    }
}
