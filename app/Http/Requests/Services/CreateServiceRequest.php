<?php

namespace App\Http\Requests\Services;

use App\Exceptions\ValidationServiceException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CreateServiceRequest extends FormRequest
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
            'name' => 'required|max:255|string|unique:services',
            'namespace_id' => 'required|min:0|numeric|exists:namespaces,id',
        ];
    }

    public function messages(): array {
        return [
            'name.required' => 'Name is required!',
            'name.string' => 'Name must be a text!',
            'name.max' => 'Name cannot be longer than 255 characters!',
            'name.unique' => 'Name already exists!',
            'namespace_id.required' => 'Namespace id is required!',
            'namespace_id.min' => 'Namespace id must be at least 0!',
            'namespace_id.numeric' => 'Namespace id must be a number!',
            'namespace_id.exists' => 'Namespace id does not exist!',
        ];
    }
}
