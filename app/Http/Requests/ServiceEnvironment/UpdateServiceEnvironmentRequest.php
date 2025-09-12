<?php

namespace App\Http\Requests\ServiceEnvironment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use App\Exceptions\ValidationServiceException;
class UpdateServiceEnvironmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        $serviceId = $this->input('service_id');
        $envId     = $this->input('environment_id');

        return [
            'service_id'     => 'sometimes|integer|exists:services,id',
            'environment_id' => [
                'sometimes','integer','exists:environments,id',
                Rule::unique('services_environments')
                    ->where(function($q) use ($serviceId, $envId) {
                        if ($serviceId !== null) {
                            $q->where('service_id', $serviceId);
                        }
                        if ($envId !== null) {
                            $q->where('environment_id', $envId);
                        }
                    })
                    ->ignore($id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.integer'      => 'service_id must be integer!',
            'service_id.exists'       => 'service_id not found!',
            'environment_id.integer'  => 'environment_id must be integer!',
            'environment_id.exists'   => 'environment_id not found!',
            'environment_id.unique'   => 'The pair service_id + environment_id already exists!',
        ];
    }
}
