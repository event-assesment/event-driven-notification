<?php

namespace App\Services\Notifications\Providers;

use App\Services\Notifications\DeliveryReceiptHandlerInterface;
use App\Services\Notifications\DTOs\DeliveryReceiptDTO;
use App\Services\Notifications\DTOs\SendRequest;
use App\Services\Notifications\DTOs\SendResult;
use App\Services\Notifications\NotificationProviderInterface;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class WebhookSiteProvider implements DeliveryReceiptHandlerInterface, NotificationProviderInterface
{
    public function __construct(private readonly string $endpoint, private readonly int $timeout = 5)
    {
    }

    public function name(): string
    {
        return 'webhook.site';
    }

    public function send(SendRequest $request): SendResult
    {
        try {
            $response = Http::timeout($this->timeout)->post($this->endpoint, [
                'to' => $request->to,
                'channel' => $request->channel,
                'content' => $request->content,
                'correlation_id' => $request->correlationId,
            ]);

            if ($response->status() === 202) {
                return new SendResult(
                    true,
                    $response->json('messageId'),
                    202
                );
            }

            if ($response->status() >= 400 && $response->status() < 500) {
                return new SendResult(
                    false,
                    null,
                    $response->status(),
                    'PROVIDER_4XX',
                    $response->body()
                );
            }

            return new SendResult(
                false,
                null,
                $response->status(),
                'PROVIDER_5XX',
                $response->body()
            );
        } catch (Throwable $exception) {
            return new SendResult(
                false,
                null,
                0,
                'PROVIDER_TIMEOUT',
                $exception->getMessage()
            );
        }
    }

    public function verify(array $headers, string $rawBody): void
    {
        $payload = $this->decodePayload($rawBody);

        $this->ensureValidReceipt($payload);
    }

    public function parse(string $rawBody): DeliveryReceiptDTO
    {
        $payload = $this->decodePayload($rawBody);

        $this->ensureValidReceipt($payload);

        $timestamp = null;

        if (is_string($payload['timestamp'] ?? null)) {
            try {
                $timestamp = new DateTimeImmutable($payload['timestamp']);
            } catch (Throwable $exception) {
                throw new RuntimeException('Invalid timestamp format.');
            }
        }

        $errorCode = null;
        $errorMessage = null;

        if ($payload['status'] === 'failed') {
            $errorCode = is_string($payload['error']['code'] ?? null) ? $payload['error']['code'] : null;
            $errorMessage = is_string($payload['error']['message'] ?? null) ? $payload['error']['message'] : null;
        }

        return new DeliveryReceiptDTO(
            provider: $this->name(),
            messageId: $payload['message_id'],
            status: $payload['status'],
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            timestamp: $timestamp,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $rawBody): array
    {
        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException('Invalid webhook payload.');
        }

        if (!is_array($payload)) {
            throw new RuntimeException('Invalid webhook payload.');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ensureValidReceipt(array $payload): void
    {
        $messageId = $payload['message_id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!is_string($messageId) || $messageId === '') {
            throw new RuntimeException('Receipt message_id is missing.');
        }

        if (!in_array($status, ['delivered', 'failed'], true)) {
            throw new RuntimeException('Receipt status is invalid.');
        }
    }
}
