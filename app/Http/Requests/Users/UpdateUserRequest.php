<?php

namespace App\Http\Requests\Users;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Models\Users;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user->hasPermission(ResourcesTypes::USERS, ActionsTypes::UPDATE);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $routeUser = $this->route('user');

        $userId = $routeUser instanceof Users
            ? $routeUser->id
            : $routeUser;
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(Users::class)->ignore($userId),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            // name
            'name.required' => 'Name is required.',
            'name.string'   => 'Name must be a valid text.',
            'name.max'      => 'Name cannot exceed :max characters.',

            // email
            'email.required' => 'Email address is required.',
            'email.string'   => 'Email must be a valid text format.',
            'email.lowercase'=> 'Email must be lowercase.',
            'email.email'    => 'Please provide a valid email address.',
            'email.max'      => 'Email cannot exceed :max characters.',
            'email.unique'   => 'This email is already in use by another user.',

            // password
            'password.required'   => 'Password is required.',
            'password.confirmed'  => 'Password confirmation does not match.',

            // role
            'role_id.required' => 'Role selection is required.',
            'role_id.integer'  => 'Role must be a valid number.',
            'role_id.exists'   => 'Selected role does not exist.',
        ];
    }
}
