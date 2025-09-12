<?php

namespace App\Http\Requests\ServiceEnvironment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use App\Exceptions\ValidationServiceException;

class CreateServiceEnvironmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'service_id'     => 'required|integer|exists:services,id',
            'environment_id' => [
                'required','integer','exists:environments,id',
                Rule::unique('services_environments')->where(fn($q) =>
                $q->where('service_id', $this->input('service_id'))
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required'     => 'service_id is required!',
            'service_id.integer'      => 'service_id must be integer!',
            'service_id.exists'       => 'service_id not found!',
            'environment_id.required' => 'environment_id is required!',
            'environment_id.integer'  => 'environment_id must be integer!',
            'environment_id.exists'   => 'environment_id not found!',
            'environment_id.unique'   => 'The pair service_id + environment_id already exists!',
        ];
    }
}
