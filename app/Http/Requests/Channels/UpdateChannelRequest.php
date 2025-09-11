<?php

namespace App\Http\Requests\Channels;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use App\Exceptions\ValidationServiceException;

class UpdateChannelRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'string|max:255|unique:channels',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a text!',
            'name.max'    => 'Name cannot be longer than 255 characters!',
            'name.unique' => 'Name already exists!',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new ValidationServiceException(['errors' => $validator->errors()->toArray()]);
    }
}
