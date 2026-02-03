<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TemplateValidationRequest',
    type: 'object',
    required: ['body'],
    properties: [
        new OA\Property(property: 'body', type: 'string'),
        new OA\Property(property: 'sample_variables', type: 'object', additionalProperties: true, nullable: true),
    ],
)]
class TemplateValidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'body' => ['required', 'string'],
            'sample_variables' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Template body is required.',
            'body.string' => 'Template body must be a string.',
            'sample_variables.array' => 'Sample variables must be an object.',
        ];
    }
}
