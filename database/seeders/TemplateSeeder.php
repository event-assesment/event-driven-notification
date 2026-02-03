<?php

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $template_count = Template::count();
        if ($template_count >= 3) {
            return;
        }
        Template::factory()->create([
            'name' => 'SMS Welcome',
            'channel' => NotificationChannel::Sms,
            'body' => 'Hello {{ $name }}, welcome aboard!',
        ]);

        Template::factory()->create([
            'name' => 'Email Receipt',
            'channel' => NotificationChannel::Email,
            'body' => 'Hi {{ $name }}, your order {{ $order_id }} is confirmed.',
        ]);

        Template::factory()->create([
            'name' => 'Push Promo',
            'channel' => NotificationChannel::Push,
            'body' => 'Hey {{ $name }}, today\'s offer: {{ $offer }}.',
        ]);
    }
}
