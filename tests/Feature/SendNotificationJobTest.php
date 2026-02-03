<?php

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Events\NotificationStatusChanged;
use App\Jobs\SendNotificationJob;
use App\Jobs\SyncNotificationStatusJob;
use App\Models\Notification;
use App\Services\Notifications\DTOs\SendRequest;
use App\Services\Notifications\DTOs\SendResult;
use App\Services\Notifications\NotificationProviderInterface;
use App\Services\Notifications\ProviderRegistry;
use App\Services\Notifications\StatusQueryableProviderInterface;
use App\Services\Templates\TemplateRenderer;
use App\Services\Templates\TemplateSafetyValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

it('broadcasts status changes on successful send', function () {
    Event::fake();

    $notification = Notification::factory()->create([
        'status' => NotificationStatus::Queued,
        'channel' => NotificationChannel::Sms,
        'content' => 'Hello',
    ]);

    $registry = new ProviderRegistry();
    $registry->register('sms', new class implements NotificationProviderInterface {
        public function send(SendRequest $request): SendResult
        {
            return new SendResult(true, 'provider-1', 202);
        }

        public function name(): string
        {
            return 'fake';
        }
    });

    $job = new SendNotificationJob($notification);
    $job->handle($registry, app(TemplateSafetyValidator::class), app(TemplateRenderer::class));

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Accepted);
    expect($notification->accepted_at)->not()->toBeNull();

    Event::assertDispatchedTimes(NotificationStatusChanged::class, 2);
});

it('retries on transient provider failures with backoff', function () {
    $notification = Notification::factory()->create([
        'status' => NotificationStatus::Queued,
        'channel' => NotificationChannel::Sms,
        'content' => 'Hello',
    ]);

    $registry = new ProviderRegistry();
    $registry->register('sms', new class implements NotificationProviderInterface {
        public function send(SendRequest $request): SendResult
        {
            return new SendResult(false, null, 500, 'PROVIDER_5XX', 'Server error');
        }

        public function name(): string
        {
            return 'fake';
        }
    });

    $job = new SendNotificationJob($notification);

    expect(fn () => $job->handle($registry, app(TemplateSafetyValidator::class), app(TemplateRenderer::class)))
        ->toThrow(RuntimeException::class);

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Queued);
    expect($job->tries)->toBe(5);
    expect($job->backoff)->toBe([5, 15, 60, 180, 600]);
});

it('respects per-channel rate limits', function () {
    config(['notifications.rate_limits.per_second' => 1]);

    $notification = Notification::factory()->create([
        'status' => NotificationStatus::Queued,
        'channel' => NotificationChannel::Sms,
        'content' => 'Hello',
    ]);

    $rateKey = 'notifications:sms';
    RateLimiter::clear($rateKey);
    RateLimiter::hit($rateKey, 1);

    $called = false;

    $registry = new ProviderRegistry();
    $registry->register('sms', new class ($called) implements NotificationProviderInterface {
        public function __construct(private bool &$called)
        {
        }

        public function send(SendRequest $request): SendResult
        {
            $this->called = true;

            return new SendResult(true, 'provider-1', 202);
        }

        public function name(): string
        {
            return 'fake';
        }
    });

    $job = new SendNotificationJob($notification);
    $job->handle($registry, app(TemplateSafetyValidator::class), app(TemplateRenderer::class));

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Queued);
    expect($called)->toBeFalse();
});

it('schedules status sync when provider supports status queries', function () {
    Bus::fake();

    $notification = Notification::factory()->create([
        'status' => NotificationStatus::Queued,
        'channel' => NotificationChannel::Sms,
        'content' => 'Hello',
    ]);

    $registry = new ProviderRegistry();
    $registry->register('sms', new class implements NotificationProviderInterface, StatusQueryableProviderInterface {
        public function send(SendRequest $request): SendResult
        {
            return new SendResult(true, 'provider-1', 202);
        }

        public function name(): string
        {
            return 'fake';
        }

        public function fetchStatus(string $providerMessageId): \App\Services\Notifications\DTOs\ProviderStatusDTO
        {
            return new \App\Services\Notifications\DTOs\ProviderStatusDTO('accepted');
        }
    });

    $job = new SendNotificationJob($notification);
    $job->handle($registry, app(TemplateSafetyValidator::class), app(TemplateRenderer::class));

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Accepted);
    expect($notification->next_status_check_at)->not()->toBeNull();

    Bus::assertDispatched(SyncNotificationStatusJob::class);
});
