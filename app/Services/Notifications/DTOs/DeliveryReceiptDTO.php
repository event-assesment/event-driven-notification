<?php

namespace App\Services\Notifications\DTOs;

use DateTimeImmutable;

final class DeliveryReceiptDTO
{
    public function __construct(
        public readonly string $provider,
        public readonly string $messageId,
        public readonly string $status,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly ?DateTimeImmutable $timestamp = null,
    ) {
    }
}
