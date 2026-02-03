<?php

namespace App\Http\Requests;

use App\Models\Notification;
use Illuminate\Foundation\Http\FormRequest;

class CancelNotificationRequest extends FormRequest
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
            'notification' => ['required', 'uuid', 'exists:notifications,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notification.required' => 'Notification id is required.',
            'notification.uuid' => 'Notification id must be a valid UUID.',
            'notification.exists' => 'Notification not found.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $notification = $this->route('notification');

        $this->merge([
            'notification' => $notification instanceof Notification ? $notification->id : $notification,
        ]);
    }
}
