<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notification_count = Notification::count();
        if ($notification_count >= 50) {
            return;
        }
        Notification::factory()->count(50)->create();
    }
}
