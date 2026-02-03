<?php

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SyncNotificationStatusJob;
use App\Models\Notification;
use App\Services\Notifications\DTOs\ProviderStatusDTO;
use App\Services\Notifications\DTOs\SendRequest;
use App\Services\Notifications\DTOs\SendResult;
use App\Services\Notifications\NotificationProviderInterface;
use App\Services\Notifications\ProviderRegistry;
use App\Services\Notifications\StatusQueryableProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('marks notification as delivered when provider reports delivered', function () {
    $notification = Notification::factory()->create([
        'status' => NotificationStatus::Accepted,
        'channel' => NotificationChannel::Sms,
        'provider_message_id' => 'provider-1',
        'accepted_at' => now()->subMinutes(10),
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

        public function fetchStatus(string $providerMessageId): ProviderStatusDTO
        {
            return new ProviderStatusDTO('delivered');
        }
    });

    $job = new SyncNotificationStatusJob($notification->id);
    $job->handle($registry);

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Delivered);
    expect($notification->delivered_at)->not()->toBeNull();
    expect($notification->next_status_check_at)->toBeNull();
});

it('schedules another check when provider status remains accepted', function () {
    Bus::fake();
    config(['notifications.status_sync.delays' => [5, 15]]);

    $notification = Notification::factory()->create([
        'status' => NotificationStatus::Accepted,
        'channel' => NotificationChannel::Sms,
        'provider_message_id' => 'provider-1',
        'accepted_at' => now()->subMinutes(5),
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

        public function fetchStatus(string $providerMessageId): ProviderStatusDTO
        {
            return new ProviderStatusDTO('accepted');
        }
    });

    $job = new SyncNotificationStatusJob($notification->id);
    $job->handle($registry);

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Accepted);
    expect($notification->next_status_check_at)->not()->toBeNull();

    Bus::assertDispatched(SyncNotificationStatusJob::class);
});

it('skips syncing when provider does not support status queries', function () {
    Bus::fake();

    $notification = Notification::factory()->create([
        'status' => NotificationStatus::Accepted,
        'channel' => NotificationChannel::Sms,
        'provider_message_id' => 'provider-1',
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

    $job = new SyncNotificationStatusJob($notification->id);
    $job->handle($registry);

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Accepted);
    expect($notification->next_status_check_at)->toBeNull();

    Bus::assertNotDispatched(SyncNotificationStatusJob::class);
});

it('is idempotent when notification is not accepted', function () {
    Bus::fake();

    $notification = Notification::factory()->create([
        'status' => NotificationStatus::Delivered,
        'channel' => NotificationChannel::Sms,
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

    $job = new SyncNotificationStatusJob($notification->id);
    $job->handle($registry);

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Delivered);

    Bus::assertNotDispatched(SyncNotificationStatusJob::class);
});
