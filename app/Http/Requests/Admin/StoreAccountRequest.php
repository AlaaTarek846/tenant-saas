<?php

namespace App\Http\Requests\Admin;

use App\Enums\AccountTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AccountTypeEnum::class)],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ];
    }
}
