<?php

namespace App\Http\Requests\Configurations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Exceptions\ValidationServiceException;
use Illuminate\Contracts\Validation\Validator;

class UpdateConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_environment_id' => ['sometimes', 'integer', 'exists:services_environments,id'],
            'channel_id'             => ['sometimes', 'integer', 'exists:channels,id'],
            'cron_expression'        => ['sometimes', 'string'],

            // ---- SOURCE (opsional) ----
            'source'                 => ['sometimes', 'required_array_keys:url,method,headers,body,timeout,retry_count'],
            'source.url'             => ['sometimes', 'string', 'active_url'],
            'source.method'          => ['sometimes', 'string', Rule::in(['GET','POST','PUT','DELETE','PATCH'])],
            'source.headers'         => ['sometimes', 'array', 'min:1'],
            'source.headers.*'       => ['sometimes', 'string'],
            'source.body'            => ['sometimes', 'array'],
            'source.timeout'         => ['sometimes', 'integer', 'min:1'],
            'source.retry_count'      => ['sometimes', 'integer', 'min:1'],

            // ---- DESTINATION (opsional) ----
            'destination'                => ['sometimes', 'required_array_keys:url,method,headers,body_template,extract,foreach,timeout,retry_count,range_per_request'],
            'destination.url'            => ['sometimes', 'string', 'active_url'],
            'destination.method'         => ['sometimes', 'string', Rule::in(['GET','POST','PUT','DELETE','PATCH'])],
            'destination.headers'        => ['sometimes', 'array', 'min:1'],
            'destination.headers.*'      => ['sometimes', 'string'],
            'destination.extract'        => ['sometimes', 'array', 'min:1'],
            'destination.extract.*'      => ['sometimes', 'string'],
            'destination.foreach'        => ['sometimes', 'string'],
            'destination.body_template'  => ['sometimes', 'json'],
            'destination.timeout'        => ['sometimes', 'integer', 'min:1'],
            'destination.retry_count'     => ['sometimes', 'integer', 'min:1'],
            'destination.range_per_request'=> ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_environment_id.integer' => 'service_environment_id must be integer!',
            'service_environment_id.exists'  => 'service_environment_id not found!',
            'channel_id.integer'             => 'channel_id must be integer!',
            'channel_id.exists'              => 'channel_id not found!',
        ];
    }
}
