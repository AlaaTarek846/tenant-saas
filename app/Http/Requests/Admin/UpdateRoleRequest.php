<?php

namespace App\Http\Requests\Admin;

use App\Repositories\Admin\RoleRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        $roleId = $this->route('role');
        $tenantId = $this->user()?->tenant_id;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->ignore($roleId)
                    ->where(fn ($query) => $query
                        ->where('guard_name', 'web')
                        ->where('tenant_id', $tenantId)),
                Rule::notIn(RoleRepository::SYSTEM_ROLES),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
