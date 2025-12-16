<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BusinessAuditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // No authentication required
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'website_url' => ['required', 'url', 'max:500'],
            'business_name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:255'],
            'country' => ['required'], // Can be string or array
            'country.*' => ['string', 'max:100'], // Validation for array items
            'city' => ['required'], // Can be string or array
            'city.*' => ['string', 'max:100'], // Validation for array items
            'target_audience' => ['required', 'string', 'max:500'],
            'competitors' => ['nullable', 'array'],
            'competitors.*' => ['string', 'max:500'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'website_url.required' => 'The website URL is required.',
            'website_url.url' => 'The website URL must be a valid URL.',
            'business_name.required' => 'The business name is required.',
            'industry.required' => 'The industry/category is required.',
            'country.required' => 'At least one country is required.',
            'city.required' => 'At least one city is required.',
            'target_audience.required' => 'The target audience is required.',
        ];
    }

    /**
     * Prepare the data for validation - normalize country and city to arrays
     */
    protected function prepareForValidation()
    {
        // Normalize country to array if it's a string
        if ($this->has('country') && is_string($this->country)) {
            $this->merge([
                'country' => [$this->country]
            ]);
        }

        // Normalize city to array if it's a string
        if ($this->has('city') && is_string($this->city)) {
            $this->merge([
                'city' => [$this->city]
            ]);
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
