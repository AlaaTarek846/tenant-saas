<?php

namespace App\Http\Requests\Admin;

use App\Enums\InvoiceStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
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
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::enum(InvoiceStatusEnum::class)],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
