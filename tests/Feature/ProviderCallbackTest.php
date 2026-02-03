<?php

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\Notifications\DTOs\DeliveryReceiptDTO;
use App\Services\Notifications\DTOs\SendRequest;
use App\Services\Notifications\DTOs\SendResult;
use App\Services\Notifications\DeliveryReceiptHandlerInterface;
use App\Services\Notifications\NotificationProviderInterface;
use App\Services\Notifications\ProviderRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('processes delivery receipt callbacks', function () {
    $notification = Notification::factory()->create([
        'status' => NotificationStatus::Accepted,
        'channel' => NotificationChannel::Sms,
        'provider_message_id' => 'abc-123',
    ]);

    $provider = new class implements NotificationProviderInterface, DeliveryReceiptHandlerInterface {
        public function send(SendRequest $request): SendResult
        {
            return new SendResult(true, 'abc-123', 202);
        }

        public function name(): string
        {
            return 'fake';
        }

        public function verify(array $headers, string $rawBody): void
        {
        }

        public function parse(string $rawBody): DeliveryReceiptDTO
        {
            return new DeliveryReceiptDTO('fake', 'abc-123', 'delivered');
        }
    };

    $registry = new ProviderRegistry();
    $registry->registerProvider('fake', $provider);
    $registry->register('sms', $provider);

    $this->app->instance(ProviderRegistry::class, $registry);

    $response = $this->postJson('/api/providers/fake/callbacks', [
        'messageId' => 'abc-123',
        'status' => 'delivered',
    ]);

    $response->assertSuccessful()->assertJsonFragment(['status' => 'delivered']);

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Delivered);
    expect($notification->delivered_at)->not()->toBeNull();
});

it('rejects callbacks when verification fails', function () {
    $provider = new class implements NotificationProviderInterface, DeliveryReceiptHandlerInterface {
        public function send(SendRequest $request): SendResult
        {
            return new SendResult(true, 'abc-123', 202);
        }

        public function name(): string
        {
            return 'fake';
        }

        public function verify(array $headers, string $rawBody): void
        {
            throw new \RuntimeException('Invalid signature');
        }

        public function parse(string $rawBody): DeliveryReceiptDTO
        {
            return new DeliveryReceiptDTO('fake', 'abc-123', 'failed');
        }
    };

    $registry = new ProviderRegistry();
    $registry->registerProvider('fake', $provider);

    $this->app->instance(ProviderRegistry::class, $registry);

    $response = $this->postJson('/api/providers/fake/callbacks', [
        'messageId' => 'abc-123',
        'status' => 'delivered',
    ]);

    $response->assertUnauthorized();
});
