<?php

use App\Services\Notifications\DTOs\DeliveryReceiptDTO;
use App\Services\Notifications\Providers\WebhookSiteProvider;
it('parses a delivered receipt payload', function () {
    $provider = new WebhookSiteProvider('https://webhook.site/example', 5);
    $payload = json_encode([
        'message_id' => 'msg-123',
        'status' => 'delivered',
        'timestamp' => '2026-02-02T12:00:00+00:00',
    ], JSON_THROW_ON_ERROR);

    $receipt = $provider->parse($payload);

    expect($receipt)->toBeInstanceOf(DeliveryReceiptDTO::class)
        ->and($receipt->messageId)->toBe('msg-123')
        ->and($receipt->status)->toBe('delivered')
        ->and($receipt->errorCode)->toBeNull()
        ->and($receipt->errorMessage)->toBeNull()
        ->and($receipt->timestamp?->format(DATE_ATOM))->toBe('2026-02-02T12:00:00+00:00');
});

it('parses a failed receipt payload with error details', function () {
    $provider = new WebhookSiteProvider('https://webhook.site/example', 5);
    $payload = json_encode([
        'message_id' => 'msg-456',
        'status' => 'failed',
        'error' => [
            'code' => 'PROVIDER_ERROR',
            'message' => 'Delivery failed.',
        ],
    ], JSON_THROW_ON_ERROR);

    $receipt = $provider->parse($payload);

    expect($receipt->status)->toBe('failed')
        ->and($receipt->errorCode)->toBe('PROVIDER_ERROR')
        ->and($receipt->errorMessage)->toBe('Delivery failed.');
});

it('rejects invalid receipt payloads', function () {
    $provider = new WebhookSiteProvider('https://webhook.site/example', 5);

    expect(fn () => $provider->verify([], 'not-json'))->toThrow(\RuntimeException::class);

    $missingMessageId = json_encode(['status' => 'delivered'], JSON_THROW_ON_ERROR);
    expect(fn () => $provider->verify([], $missingMessageId))->toThrow(\RuntimeException::class);

    $invalidStatus = json_encode(['message_id' => 'msg-1', 'status' => 'accepted'], JSON_THROW_ON_ERROR);
    expect(fn () => $provider->verify([], $invalidStatus))->toThrow(\RuntimeException::class);
});
