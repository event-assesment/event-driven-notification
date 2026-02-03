<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Events\NotificationStatusChanged;
use App\Models\Notification;
use App\Jobs\SyncNotificationStatusJob;
use App\Services\Notifications\DTOs\SendRequest;
use App\Services\Notifications\ProviderRegistry;
use App\Services\Notifications\StatusQueryableProviderInterface;
use App\Services\Templates\TemplateRenderer;
use App\Services\Templates\TemplateSafetyValidator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Throwable;
use function event;

class SendNotificationJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 15, 60, 180, 600];

    public function __construct(public Notification $notification)
    {
    }

    public function handle(ProviderRegistry $registry, TemplateSafetyValidator $validator, TemplateRenderer $renderer): void
    {
        $notification = $this->notification->fresh();

        if (!$notification instanceof Notification) {
            return;
        }

        if ($notification->status === NotificationStatus::Canceled) {
            return;
        }

        if ($notification->status === NotificationStatus::Scheduled && $notification->scheduled_at?->isFuture()) {
            $this->release($notification->scheduled_at->diffInSeconds(now()));

            return;
        }

        $channel = $notification->channel->value;
        $rateLimit = (int) config('notifications.rate_limits.per_second', 100);
        $rateKey = "notifications:{$channel}";
        $decaySeconds = 1;

        if (!RateLimiter::attempt($rateKey, $rateLimit, static fn (): bool => true, $decaySeconds)) {
            $this->release($decaySeconds);

            return;
        }

        $content = $notification->content;

        if ($notification->template_id !== null) {
            $template = $notification->template()->first();

            if ($template === null) {
                $this->markFailed($notification, 'TEMPLATE_MISSING', 'Template not found.');

                return;
            }

            if ($template->channel->value !== $channel) {
                $this->markFailed($notification, 'TEMPLATE_CHANNEL_MISMATCH', 'Template channel does not match notification channel.');

                return;
            }

            $errors = $validator->validate($template->body);

            if ($errors !== []) {
                $this->markFailed($notification, 'TEMPLATE_FORBIDDEN', implode(' ', $errors));

                return;
            }

            $content = $renderer->render($template->body, $notification->variables ?? []);
        }

        if (!is_string($content) || $content === '') {
            $this->markFailed($notification, 'CONTENT_MISSING', 'Notification content is missing.');

            return;
        }

        $maxLength = config("notifications.channels.{$channel}.max_length");

        if (is_int($maxLength) && mb_strlen($content) > $maxLength) {
            $this->markFailed($notification, 'CONTENT_TOO_LONG', 'Notification content exceeds channel limits.');

            return;
        }

        $notification->status = NotificationStatus::Sending;
        $notification->attempts = max($notification->attempts + 1, $this->attempts());
        $notification->last_error = null;
        $notification->save();

        $this->broadcastStatus($notification);

        try {
            $provider = $registry->resolve($channel);
            $result = $provider->send(new SendRequest(
                to: $notification->to,
                channel: $channel,
                content: $content,
                correlationId: (string) $notification->correlation_id,
            ));

            if ($result->accepted) {
                $notification->status = NotificationStatus::Accepted;
                $notification->provider_message_id = $result->providerMessageId;
                $notification->accepted_at = now();
                $notification->last_error = null;
                $notification->save();

                $this->broadcastStatus($notification);

                if ($provider instanceof StatusQueryableProviderInterface && $notification->provider_message_id) {
                    $this->scheduleStatusSync($notification);
                }

                return;
            }

            if ($result->httpStatus >= 400 && $result->httpStatus < 500) {
                $this->markFailed($notification, $result->errorCode ?? 'PROVIDER_4XX', $result->errorMessage);

                return;
            }

            $notification->status = NotificationStatus::Queued;
            $notification->last_error = $result->errorMessage;
            $notification->save();

            $this->broadcastStatus($notification);

            throw new RuntimeException($result->errorMessage ?? 'Transient provider error.');
        } catch (Throwable $exception) {
            $notification->status = NotificationStatus::Queued;
            $notification->last_error = $exception->getMessage();
            $notification->save();

            $this->broadcastStatus($notification);

            throw $exception;
        }
    }

    private function markFailed(Notification $notification, string $errorCode, ?string $errorMessage): void
    {
        $notification->status = NotificationStatus::Failed;
        $notification->last_error = $errorMessage ?? $errorCode;
        $notification->save();

        $this->broadcastStatus($notification);

        $this->fail(new RuntimeException($errorMessage ?? $errorCode));
    }

    private function broadcastStatus(Notification $notification): void
    {
        event(new NotificationStatusChanged($notification));
    }

    private function scheduleStatusSync(Notification $notification): void
    {
        $delays = (array) config('notifications.status_sync.delays', [5, 15, 60, 360]);
        $delayMinutes = (int) ($delays[0] ?? 5);

        $notification->next_status_check_at = now()->addMinutes($delayMinutes);
        $notification->save();

        dispatch((new SyncNotificationStatusJob($notification->id))
            ->onQueue((string) config('notifications.queues.status_sync'))
            ->delay($notification->next_status_check_at));
    }
}
