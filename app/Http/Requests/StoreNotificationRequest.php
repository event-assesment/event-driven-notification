<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreNotificationRequest',
    type: 'object',
    required: ['to', 'channel'],
    description: 'Provide either content or template_id + variables.',
    properties: [
        new OA\Property(property: 'to', type: 'string', maxLength: 255),
        new OA\Property(property: 'channel', type: 'string', enum: ['sms', 'email', 'push']),
        new OA\Property(property: 'content', type: 'string', nullable: true),
        new OA\Property(property: 'template_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'variables', type: 'object', additionalProperties: true, nullable: true),
        new OA\Property(property: 'priority', type: 'string', enum: ['high', 'normal', 'low']),
        new OA\Property(property: 'scheduled_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class StoreNotificationRequest extends FormRequest
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
            'to' => ['required', 'string', 'max:255'],
            'channel' => ['required', Rule::enum(NotificationChannel::class)],
            'content' => ['required_without:template_id', 'string'],
            'template_id' => ['required_without:content', 'uuid', 'exists:templates,id'],
            'variables' => ['required_with:template_id', 'array'],
            'priority' => ['nullable', Rule::enum(NotificationPriority::class)],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to.required' => 'The recipient is required.',
            'channel.required' => 'The notification channel is required.',
            'channel.enum' => 'The selected channel is invalid.',
            'content.required_without' => 'Content is required when no template is provided.',
            'template_id.required_without' => 'A template is required when content is missing.',
            'template_id.exists' => 'The selected template does not exist.',
            'variables.required_with' => 'Template variables are required when using a template.',
            'priority.enum' => 'The selected priority is invalid.',
            'scheduled_at.date' => 'Scheduled time must be a valid date.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('template_id') && $this->filled('content')) {
                $validator->errors()->add('content', 'Content is not allowed when a template is provided.');
            }

            if ($this->filled('content') && $this->filled('channel')) {
                $channel = (string) $this->input('channel');
                $maxLength = config("notifications.channels.{$channel}.max_length");

                if (is_int($maxLength) && mb_strlen((string) $this->input('content')) > $maxLength) {
                    $validator->errors()->add('content', 'Notification content exceeds channel limits.');
                }
            }
        });
    }
}
