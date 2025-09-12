<?php

namespace App\Http\Requests\Configurations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use App\Exceptions\ValidationServiceException;


class UpdateConfigurationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        $seId = $this->input('service_environment_id');
        $chId = $this->input('channel_id');

        return [
            'service_environment_id' => 'sometimes|integer|exists:services_environments,id',
            'channel_id'             => [
                'sometimes','integer','exists:channels,id',
                Rule::unique('configurations')
                    ->where(function($q) use ($seId, $chId) {
                        if ($seId !== null) {
                            $q->where('service_environment_id', $seId);
                        }
                        if ($chId !== null) {
                            $q->where('channel_id', $chId);
                        }
                    })
                    ->ignore($id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'service_environment_id.integer' => 'service_environment_id must be integer!',
            'service_environment_id.exists'  => 'service_environment_id not found!',
            'channel_id.integer'             => 'channel_id must be integer!',
            'channel_id.exists'              => 'channel_id not found!',
            'channel_id.unique'              => 'The pair service_environment_id + channel_id already exists!',
        ];
    }

}
