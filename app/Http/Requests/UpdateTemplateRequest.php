<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Services\Templates\TemplateSafetyValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateTemplateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255),
        new OA\Property(property: 'channel', type: 'string', enum: ['sms', 'email', 'push']),
        new OA\Property(property: 'body', type: 'string'),
    ],
)]
class UpdateTemplateRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'channel' => ['sometimes', Rule::enum(NotificationChannel::class)],
            'body' => ['sometimes', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Template name must be a string.',
            'name.max' => 'Template name cannot exceed 255 characters.',
            'channel.enum' => 'The selected channel is invalid.',
            'body.string' => 'Template body must be a string.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!$this->has('body')) {
                return;
            }

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
