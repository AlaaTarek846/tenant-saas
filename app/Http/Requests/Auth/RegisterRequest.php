<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'company.name' => ['required', 'string', 'max:255'],
            'company.email' => ['required', 'string', 'email', 'max:255', 'unique:tenants,email'],
            'company.country' => ['nullable', 'string', 'max:255'],
            'company.city' => ['nullable', 'string', 'max:255'],
            'company.phone' => ['nullable', 'string', 'max:50'],
            'admin.name' => ['required', 'string', 'max:15'],
            'admin.email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'admin.password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company.name.required' => 'اسم الشركة مطلوب.',
            'company.email.required' => 'بريد الشركة مطلوب.',
            'company.email.unique' => 'بريد الشركة مستخدم بالفعل.',
            'admin.name.required' => 'اسم مدير الشركة مطلوب.',
            'admin.name.max' => 'اسم مدير الشركة لا يزيد عن 15 حرف.',
            'admin.email.required' => 'بريد مدير الشركة مطلوب.',
            'admin.email.unique' => 'بريد مدير الشركة مستخدم بالفعل.',
            'admin.password.required' => 'كلمة المرور مطلوبة.',
            'admin.password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}
