<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var NotificationChannel $channel */
        $channel = fake()->randomElement(NotificationChannel::cases());
        $to = match ($channel) {
            NotificationChannel::Sms => '+1'.fake()->numerify('##########'),
            NotificationChannel::Email => fake()->safeEmail(),
            NotificationChannel::Push => fake()->uuid(),
        };

        return [
            'batch_id' => (string) Str::uuid(),
            'idempotency_key' => null,
            'template_id' => null,
            'to' => $to,
            'channel' => $channel,
            'content' => fake()->sentence(),
            'variables' => null,
            'priority' => NotificationPriority::Normal,
            'status' => NotificationStatus::Pending,
            'provider_message_id' => (string) Str::uuid(),
            'attempts' => 0,
            'last_error' => null,
            'correlation_id' => (string) Str::uuid(),
            'scheduled_at' => null,
            'accepted_at' => null,
            'delivered_at' => null,
            'last_status_check_at' => null,
            'next_status_check_at' => null,
        ];
    }
}
