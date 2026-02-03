<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Template>
 */
class TemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'channel' => fake()->randomElement(NotificationChannel::cases()),
            'body' => 'Hello {{ $name }}',
        ];
    }
}
