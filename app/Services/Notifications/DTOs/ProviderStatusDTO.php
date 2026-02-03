<?php

namespace App\Services\Notifications\DTOs;

use DateTimeImmutable;

final class ProviderStatusDTO
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly ?DateTimeImmutable $timestamp = null,
    ) {
    }
}
