<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Events\NotificationStatusChanged;
use App\Models\Notification;
use App\Services\Notifications\ProviderRegistry;
use App\Services\Notifications\StatusQueryableProviderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use function event;

class SyncNotificationStatusJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public string $notificationId)
    {
    }

    public function handle(ProviderRegistry $registry): void
    {
        $notification = Notification::query()->find($this->notificationId);

        if (!$notification instanceof Notification) {
            return;
        }

        if ($notification->status !== NotificationStatus::Accepted) {
            return;
        }

        if (!is_string($notification->provider_message_id) || $notification->provider_message_id === '') {
            return;
        }

        $provider = $registry->resolve($notification->channel->value);

        if (!$provider instanceof StatusQueryableProviderInterface) {
            return;
        }

        $ttlHours = (int) config('notifications.status_sync.ttl_hours', 24);
        $acceptedAt = $notification->accepted_at ?? $notification->updated_at ?? now();

        if ($acceptedAt->copy()->addHours($ttlHours)->isPast()) {
            $notification->status = NotificationStatus::Unknown;
            $notification->next_status_check_at = null;
            $notification->last_status_check_at = now();
            $notification->save();

            event(new NotificationStatusChanged($notification));

            return;
        }

        try {
            $status = $provider->fetchStatus($notification->provider_message_id);
        } catch (Throwable $exception) {
            $notification->last_error = $exception->getMessage();
            $notification->last_status_check_at = now();
            $notification->save();

            $this->scheduleNextCheck($notification);

            return;
        }

        $notification->last_status_check_at = now();

        if ($status->status === 'delivered') {
            $notification->status = NotificationStatus::Delivered;
            $notification->delivered_at = $status->timestamp ?? now();
            $notification->last_error = null;
            $notification->next_status_check_at = null;
            $notification->save();

            event(new NotificationStatusChanged($notification));

            return;
        }

        if ($status->status === 'failed') {
            $notification->status = NotificationStatus::Failed;
            $notification->last_error = $status->errorMessage ?? $status->errorCode;
            $notification->next_status_check_at = null;
            $notification->save();

            event(new NotificationStatusChanged($notification));

            return;
        }

        $notification->save();

        $this->scheduleNextCheck($notification);
    }

    private function scheduleNextCheck(Notification $notification): void
    {
        $delays = (array) config('notifications.status_sync.delays', [5, 15, 60, 360]);

        $nextDelay = $this->resolveNextDelay($notification, $delays);
        $notification->next_status_check_at = now()->addMinutes($nextDelay);
        $notification->save();

        dispatch((new self($notification->id))
            ->onQueue((string) config('notifications.queues.status_sync'))
            ->delay($notification->next_status_check_at));
    }

    /**
     * @param  array<int, int>  $delays
     */
    private function resolveNextDelay(Notification $notification, array $delays): int
    {
        $delays = array_values(array_map('intval', $delays));

        if ($delays === []) {
            return 5;
        }

        $previousDelay = $this->previousDelayMinutes($notification);

        if ($previousDelay === null) {
            return $delays[0];
        }

        $index = array_search($previousDelay, $delays, true);

        if ($index === false) {
            return end($delays);
        }

        return $delays[$index + 1] ?? $delays[$index];
    }

    private function previousDelayMinutes(Notification $notification): ?int
    {
        if ($notification->next_status_check_at === null) {
            return null;
        }

        $reference = $notification->last_status_check_at
            ?? $notification->accepted_at
            ?? $notification->updated_at;

        if ($reference === null) {
            return null;
        }

        return (int) $notification->next_status_check_at->diffInMinutes($reference);
    }
}
