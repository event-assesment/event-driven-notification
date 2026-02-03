<?php

namespace App\Services\Notifications\DTOs;

final class SendRequest
{
    public function __construct(
        public readonly string $to,
        public readonly string $channel,
        public readonly string $content,
        public readonly string $correlationId,
    ) {
    }
}
