<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
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
            'invoice_id' => ['sometimes', 'integer', 'exists:invoices,id'],
            'payment_method' => ['sometimes', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'paid_at' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::enum(PaymentStatusEnum::class)],
        ];
    }
}
