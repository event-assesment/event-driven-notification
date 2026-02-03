<?php

namespace App\Console\Commands;

use App\Enums\NotificationStatus;
use App\Events\NotificationStatusChanged;
use App\Models\Notification;
use Illuminate\Console\Command;
use function event;

class NotificationsMarkUnknown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:mark-unknown';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark stale accepted notifications as unknown';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ttlHours = (int) config('notifications.status_sync.ttl_hours', 24);
        $cutoff = now()->subHours($ttlHours);

        Notification::query()
            ->where('status', NotificationStatus::Accepted)
            ->where('accepted_at', '<=', $cutoff)
            ->chunkById(100, function ($notifications): void {
                foreach ($notifications as $notification) {
                    $notification->status = NotificationStatus::Unknown;
                    $notification->next_status_check_at = null;
                    $notification->last_status_check_at = now();
                    $notification->save();

                    event(new NotificationStatusChanged($notification));
                }
            });

        return self::SUCCESS;
    }
}
