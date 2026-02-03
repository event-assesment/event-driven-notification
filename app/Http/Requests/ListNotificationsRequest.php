<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListNotificationsRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(NotificationStatus::class)],
            'channel' => ['nullable', Rule::enum(NotificationChannel::class)],
            'priority' => ['nullable', Rule::enum(NotificationPriority::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.enum' => 'The selected status is invalid.',
            'channel.enum' => 'The selected channel is invalid.',
            'priority.enum' => 'The selected priority is invalid.',
            'per_page.integer' => 'Per page must be a number.',
            'per_page.max' => 'Per page cannot exceed 100.',
            'created_from.date' => 'Created from must be a valid date.',
            'created_to.date' => 'Created to must be a valid date.',
            'created_to.after_or_equal' => 'Created to must be after or equal to created from.',
        ];
    }
}
