<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Services\Templates\TemplateSafetyValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreTemplateRequest',
    type: 'object',
    required: ['name', 'channel', 'body'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255),
        new OA\Property(property: 'channel', type: 'string', enum: ['sms', 'email', 'push']),
        new OA\Property(property: 'body', type: 'string'),
    ],
)]
class StoreTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', Rule::enum(NotificationChannel::class)],
            'body' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Template name is required.',
            'name.string' => 'Template name must be a string.',
            'name.max' => 'Template name cannot exceed 255 characters.',
            'channel.required' => 'Template channel is required.',
            'channel.enum' => 'The selected channel is invalid.',
            'body.required' => 'Template body is required.',
            'body.string' => 'Template body must be a string.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $body = $this->input('body');

            if (!is_string($body)) {
                return;
            }

            $errors = app(TemplateSafetyValidator::class)->validate($body);

            foreach ($errors as $error) {
                $validator->errors()->add('body', $error);
            }
        });
    }
}
