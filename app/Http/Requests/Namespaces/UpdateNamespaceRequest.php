<?php

namespace App\Http\Requests\Namespaces;

use App\Exceptions\ValidationServiceException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNamespaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string|max:255|unique:namespaces'
        ];
    }

    public function messages(): array {
        return [
            'name.string' => 'Name must be a text!',
            'name.max' => 'Name cannot be longer than 255 characters!',
            'name.unique' => 'Name already exists!',
        ];
    }
}
