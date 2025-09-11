<?php

namespace App\Http\Requests\Environments;

use App\Exceptions\ValidationServiceException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
class CreateEnvironmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:environments',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required!',
            'name.string'   => 'Name must be a text!',
            'name.max'      => 'Name cannot be longer than 255 characters!',
            'name.unique'   => 'Name already exists!',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new ValidationServiceException(['errors' => $validator->errors()->toArray()]);
    }
}
