<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreBatchNotificationRequest',
    type: 'object',
    required: ['notifications'],
    properties: [
        new OA\Property(
            property: 'notifications',
            type: 'array',
            minItems: 1,
            maxItems: 1000,
            items: new OA\Items(ref: '#/components/schemas/StoreNotificationRequest')
        ),
    ],
)]
class StoreBatchNotificationRequest extends FormRequest
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
            'notifications' => ['required', 'array', 'min:1', 'max:1000'],
            'notifications.*.to' => ['required', 'string', 'max:255'],
            'notifications.*.channel' => ['required', Rule::enum(NotificationChannel::class)],
            'notifications.*.content' => ['required_without:notifications.*.template_id', 'string'],
            'notifications.*.template_id' => ['required_without:notifications.*.content', 'uuid', 'exists:templates,id'],
            'notifications.*.variables' => ['required_with:notifications.*.template_id', 'array'],
            'notifications.*.priority' => ['nullable', Rule::enum(NotificationPriority::class)],
            'notifications.*.scheduled_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notifications.required' => 'Notifications payload is required.',
            'notifications.array' => 'Notifications must be an array.',
            'notifications.max' => 'You can create up to 1000 notifications in a batch.',
            'notifications.*.to.required' => 'Each notification must include a recipient.',
            'notifications.*.channel.required' => 'Each notification must include a channel.',
            'notifications.*.channel.enum' => 'One of the notification channels is invalid.',
            'notifications.*.content.required_without' => 'Content is required when no template is provided.',
            'notifications.*.template_id.required_without' => 'Template is required when content is missing.',
            'notifications.*.template_id.exists' => 'One of the templates does not exist.',
            'notifications.*.variables.required_with' => 'Template variables are required when using a template.',
            'notifications.*.priority.enum' => 'One of the priorities is invalid.',
            'notifications.*.scheduled_at.date' => 'Scheduled time must be a valid date.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $notifications = $this->input('notifications', []);

            if (!is_array($notifications)) {
                return;
            }

            foreach ($notifications as $index => $notification) {
                if (!is_array($notification)) {
                    continue;
                }

                if (!empty($notification['template_id']) && ! empty($notification['content'])) {
                    $validator->errors()->add("notifications.{$index}.content", 'Content is not allowed when a template is provided.');
                }

                if (!empty($notification['content']) && ! empty($notification['channel'])) {
                    $channel = (string) $notification['channel'];
                    $maxLength = config("notifications.channels.{$channel}.max_length");

                    if (is_int($maxLength) && mb_strlen((string) $notification['content']) > $maxLength) {
                        $validator->errors()->add("notifications.{$index}.content", 'Notification content exceeds channel limits.');
                    }
                }
            }
        });
    }
}
