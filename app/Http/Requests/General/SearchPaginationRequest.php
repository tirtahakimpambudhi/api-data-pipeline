<?php

namespace App\Http\Requests\General;

use App\Exceptions\ValidationServiceException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SearchPaginationRequest extends FormRequest
{
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
            'page' => [
                'integer',
                'gte:0',
            ],
            'size' => [
                'integer',
                'gte:0',
            ],
            'search' => [
                'required',
                'string',
            ]
        ];
    }


    /**
     * @throws ValidationServiceException
     */
    public function failedValidation(Validator $validator)
    {
        throw new ValidationServiceException(['errors' => $validator->errors()->toArray()]);
    }



    public function messages(): array {
        return [
            'page.integer' => 'Page must be a whole number!',
            'page.gte' => 'Page must be greater than 0!',
            'size.integer' => 'Size must be a whole number!',
            'size.gte' => 'Size must be greater than 0!',
            'search.required' => 'Please enter a search value!',
            'search.string' => 'Search value must be text!',
        ];
    }

    public function validationData() :array
    {
        return [
            'page' => $this->query->getInt('page'),
            'size' => $this->query->getInt('size'),
            'search' => $this->query->getString('search'),
        ];
    }

}
