<?php

namespace App\Http\Requests\Configurations;

use App\Rules\CronRule;
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
            ],

            'source' => [
                'required',
                'required_array_keys:url,method,headers,body,timeout,retry_count',
            ],
            'source.url' => [
                'required',
                'string',
                'active_url'
            ],
            'source.method' => [
                'required',
                'string',
                Rule::in(['GET', 'POST', 'PUT', 'DELETE'])
            ],
            'source.headers' => [
                'required',
                'array',
                'min:1'
            ],
            'source.headers.*' => [
                'required',
                'string',
            ],
            'source.body' => [
                'nullable',
                'array',
            ],
            'source.timeout' => [
                'required',
                'integer',
                'min:1',
            ],
            'source.retry_count' => [
                'required',
                'integer',
                'min:1',
            ],

            'destination' => [
                'required',
                'required_array_keys:url,method,headers,body_template,extract,foreach,timeout,retry_count,range_per_request',
            ],
            'destination.url' => [
                'required',
                'string',
                'active_url'
            ],
            'destination.method' => [
                'required',
                'string',
                Rule::in(['GET', 'POST', 'PUT', 'DELETE'])
            ],
            'destination.headers' => [
                'required',
                'array',
                'min:1'
            ],
            'destination.headers.*' => [
                'required',
                'string',
            ],
            'destination.extract' => [
                'required',
                'array',
                'min:1'
            ],
            'destination.extract.*' => [
                'required',
                'string',
            ],
            'destination.foreach' => [
                'nullable',
                'string',
            ],
            'destination.body_template' => [
                'required',
                'json',
            ],
            'destination.timeout' => [
                'required',
                'integer',
                'min:1',
            ],
            'destination.retry_count' => [
                'required',
                'integer',
                'min:1',
            ],
            'destination.range_per_request' => [
                'required',
                'integer',
                'min:1',
            ],
            'cron_expression' => [
                'required',
                'string',
                new CronRule()
            ]
        ];
    }

    public function messages(): array
    {
        return [
            // === service_environment_id ===
            'service_environment_id.required' => 'service_environment_id is required!',
            'service_environment_id.integer' => 'service_environment_id must be an integer!',
            'service_environment_id.exists' => 'The specified service_environment_id does not exist!',

            // === channel_id ===
            'channel_id.required' => 'channel_id is required!',
            'channel_id.integer' => 'channel_id must be an integer!',
            'channel_id.exists' => 'The specified channel_id does not exist!',

            // === source ===
            'source.required' => 'source configuration is required!',
            'source.array' => 'source must be an array containing url, method, headers, body, timeout, and retry_count.',

            'source.url.required' => 'source.url is required!',
            'source.url.string' => 'source.url must be a string!',
            'source.url.active_url' => 'source.url must be a valid and active URL!',

            'source.method.required' => 'source.method is required!',
            'source.method.string' => 'source.method must be a string!',
            'source.method.in' => 'source.method must be one of GET, POST, PUT, or DELETE!',

            'source.headers.required' => 'source.headers is required!',
            'source.headers.array' => 'source.headers must be an array!',
            'source.headers.min' => 'source.headers must contain at least one header!',
            'source.headers.*.required' => 'Each source.headers entry is required!',
            'source.headers.*.string' => 'Each source.headers entry must be a string!',

            'source.body.array' => 'source.body must be an array if provided!',

            'source.timeout.required' => 'source.timeout is required!',
            'source.timeout.integer' => 'source.timeout must be an integer!',
            'source.timeout.min' => 'source.timeout must be at least 1 second!',

            'source.retry_count.required' => 'source.retry_count is required!',
            'source.retry_count.integer' => 'source.retry_count must be an integer!',
            'source.retry_count.min' => 'source.retry_count must be at least 1!',

            // === destination ===
            'destination.required' => 'destination configuration is required!',
            'destination.array' => 'destination must be an array containing url, method, headers, extract, body_template, etc.',

            'destination.url.required' => 'destination.url is required!',
            'destination.url.string' => 'destination.url must be a string!',
            'destination.url.active_url' => 'destination.url must be a valid and active URL!',

            'destination.method.required' => 'destination.method is required!',
            'destination.method.string' => 'destination.method must be a string!',
            'destination.method.in' => 'destination.method must be one of GET, POST, PUT, or DELETE!',

            'destination.headers.required' => 'destination.headers is required!',
            'destination.headers.array' => 'destination.headers must be an array!',
            'destination.headers.min' => 'destination.headers must contain at least one header!',
            'destination.headers.*.required' => 'Each destination.headers entry is required!',
            'destination.headers.*.string' => 'Each destination.headers entry must be a string!',

            'destination.extract.required' => 'destination.extract is required!',
            'destination.extract.array' => 'destination.extract must be an array!',
            'destination.extract.min' => 'destination.extract must contain at least one extraction rule!',
            'destination.extract.*.required' => 'Each destination.extract key is required!',
            'destination.extract.*.string' => 'Each destination.extract value must be a string!',

            'destination.foreach.string' => 'destination.foreach must be a string when provided!',

            'destination.body_template.required' => 'destination.body_template is required!',
            'destination.body_template.json' => 'destination.body_template must be a valid JSON string!',

            'destination.timeout.required' => 'destination.timeout is required!',
            'destination.timeout.integer' => 'destination.timeout must be an integer!',
            'destination.timeout.min' => 'destination.timeout must be at least 1 second!',

            'destination.retry_count.required' => 'destination.retry_count is required!',
            'destination.retry_count.integer' => 'destination.retry_count must be an integer!',
            'destination.retry_count.min' => 'destination.retry_count must be at least 1!',

            'destination.range_per_request.required' => 'destination.range_per_request is required!',
            'destination.range_per_request.integer' => 'destination.range_per_request must be an integer!',
            'destination.range_per_request.min' => 'destination.range_per_request must be at least 1!',
        ];
    }
}
