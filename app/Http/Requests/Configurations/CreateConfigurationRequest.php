<?php

namespace App\Http\Requests\Configurations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use App\Exceptions\ValidationServiceException;

class CreateConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_environment_id' => 'required|integer|exists:services_environments,id',
            'channel_id' => [
                'required', 'integer', 'exists:channels,id',
                    Rule::unique('configurations')->where(fn($q) => $q->where('service_environment_id', $this->input('service_environment_id'))
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'service_environment_id.required' => 'service_environment_id is required!',
            'service_environment_id.integer' => 'service_environment_id must be integer!',
            'service_environment_id.exists' => 'service_environment_id not found!',
            'channel_id.required' => 'channel_id is required!',
            'channel_id.integer' => 'channel_id must be integer!',
            'channel_id.exists' => 'channel_id not found!',
            'channel_id.unique' => 'The pair service_environment_id + channel_id already exists!',
        ];
    }

}
