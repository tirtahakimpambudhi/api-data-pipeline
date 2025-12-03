<?php

namespace App\Http\Requests\Roles;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRolesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user->hasPermission(ResourcesTypes::ROLES, ActionsTypes::UPDATE) && ($user->hasPermission(ResourcesTypes::ROLES_PERMISSIONS, ActionsTypes::CREATE) && $user->hasPermission(ResourcesTypes::ROLES, ActionsTypes::UPDATE));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'permissions_ids' => ['required', 'array',],
            'permissions_ids.*' => ['required','integer', 'exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            // name
            'name.required' => 'Role name is required.',
            'name.string'   => 'Role name must be a valid text.',
            'name.max'      => 'Role name cannot exceed :max characters.',

            // description
            'description.required' => 'Description is required.',
            'description.string'   => 'Description must be a valid text.',
            'description.max'      => 'Description cannot exceed :max characters.',

            // permissions array
            'permissions_ids.required' => 'At least one permission must be selected.',
            'permissions_ids.array'    => 'Permissions must be in list format.',

            // each permission
            'permissions_ids.*.required' => 'Each permission ID is required.',
            'permissions_ids.*.integer'  => 'Each permission ID must be a valid number.',
            'permissions_ids.*.exists'   => 'One or more selected permissions do not exist.',
        ];
    }
}
