<?php

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Events\NotificationStatusChanged;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('creates a notification and dispatches a job', function () {
    Bus::fake();
    Event::fake();

    $payload = [
        'to' => '+15555555555',
        'channel' => 'sms',
        'content' => 'Hello from the queue',
        'priority' => 'high',
    ];

    $response = $this->postJson('/api/notifications', $payload);

    $response
        ->assertCreated()
        ->assertJsonStructure(['id', 'batch_id', 'status']);

    $notification = Notification::query()->first();

    expect($notification)->not()->toBeNull();
    expect($notification->status)->toBe(NotificationStatus::Queued);

    Bus::assertDispatched(SendNotificationJob::class);
    Event::assertDispatched(NotificationStatusChanged::class);
});

it('creates a batch notification with a template', function () {
    Bus::fake();
    Event::fake();

    $template = Template::factory()->create([
        'channel' => NotificationChannel::Email,
        'body' => 'Hi {{ $name }}',
    ]);

    $payload = [
        'notifications' => [
            [
                'to' => 'user@example.com',
                'channel' => 'email',
                'template_id' => $template->id,
                'variables' => ['name' => 'Taylor'],
            ],
        ],
    ];

    $response = $this->postJson('/api/notifications/batch', $payload);

    $response
        ->assertCreated()
        ->assertJsonStructure(['batch_id', 'data']);

    $notification = Notification::query()->first();

    expect($notification)->not()->toBeNull();
    expect($notification->template_id)->toBe($template->id);
    expect($notification->content)->toBeNull();
    expect($notification->variables)->toBe(['name' => 'Taylor']);

    Bus::assertDispatched(SendNotificationJob::class);
});

it('reuses idempotency key for single notification requests', function () {
    Bus::fake();
    Event::fake();

    $idempotencyKey = 'notification-key-123';
    $payload = [
        'to' => '+15555555555',
        'channel' => 'sms',
        'content' => 'Hello from the queue',
        'priority' => 'high',
    ];

    $first = $this->postJson('/api/notifications', $payload, [
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $second = $this->postJson('/api/notifications', $payload, [
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $first->assertCreated();
    $second->assertCreated();

    expect(Notification::count())->toBe(1);
    expect($first->json('id'))->toBe($second->json('id'));

    $notification = Notification::query()->first();
    expect($notification->idempotency_key)->toBe($idempotencyKey);

    Bus::assertDispatchedTimes(SendNotificationJob::class, 1);
    Event::assertDispatchedTimes(NotificationStatusChanged::class, 1);
});

it('reuses idempotency key for batch notification requests', function () {
    Bus::fake();

    $template = Template::factory()->create([
        'channel' => NotificationChannel::Email,
        'body' => 'Hi {{ $name }}',
    ]);

    $idempotencyKey = 'batch-key-456';
    $payload = [
        'notifications' => [
            [
                'to' => 'user@example.com',
                'channel' => 'email',
                'template_id' => $template->id,
                'variables' => ['name' => 'Taylor'],
            ],
        ],
    ];

    $first = $this->postJson('/api/notifications/batch', $payload, [
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $second = $this->postJson('/api/notifications/batch', $payload, [
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $first->assertCreated();
    $second->assertCreated();

    expect(Notification::count())->toBe(1);
    expect($first->json('batch_id'))->toBe($second->json('batch_id'));

    $notification = Notification::query()->first();
    expect($notification->idempotency_key)->toBe($idempotencyKey);

    Bus::assertDispatchedTimes(SendNotificationJob::class, 1);
});

it('rejects idempotency key reuse with a different payload', function () {
    Bus::fake();

    $payload = [
        'to' => '+15555555555',
        'channel' => 'sms',
        'content' => 'Hello from the queue',
        'priority' => 'high',
    ];

    $idempotencyKey = 'conflict-key';

    $this->postJson('/api/notifications', $payload, [
        'Idempotency-Key' => $idempotencyKey,
    ])->assertCreated();

    $conflictPayload = [
        'to' => '+15555555555',
        'channel' => 'sms',
        'content' => 'Different message',
        'priority' => 'high',
    ];

    $this->postJson('/api/notifications', $conflictPayload, [
        'Idempotency-Key' => $idempotencyKey,
    ])->assertStatus(409);

    expect(Notification::count())->toBe(1);
});

it('filters notifications by created date range', function () {
    $from = now()->subDays(2)->startOfDay();
    $to = now()->subDay()->endOfDay();

    $before = Notification::factory()->create([
        'created_at' => $from->copy()->subDay(),
    ]);

    $inRange = Notification::factory()->create([
        'created_at' => $from->copy()->addHours(4),
    ]);

    $after = Notification::factory()->create([
        'created_at' => $to->copy()->addDay(),
    ]);

    $response = $this->getJson('/api/notifications?created_from='.$from->toISOString().'&created_to='.$to->toISOString());

    $response->assertSuccessful()->assertJsonCount(1, 'data');
    expect($response->json('data.0.id'))->toBe($inRange->id);
});

it('cancels a queued notification', function () {
    $notification = Notification::factory()->create([
        'status' => NotificationStatus::Queued,
        'channel' => NotificationChannel::Sms,
        'content' => 'Cancel me',
    ]);

    $response = $this->postJson("/api/notifications/{$notification->id}/cancel");

    $response->assertSuccessful();

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Canceled);
});
