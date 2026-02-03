<?php

namespace App\Services\Notifications\DTOs;

final class SendResult
{
    public function __construct(
        public readonly bool $accepted,
        public readonly ?string $providerMessageId,
        public readonly int $httpStatus,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {
    }
}
