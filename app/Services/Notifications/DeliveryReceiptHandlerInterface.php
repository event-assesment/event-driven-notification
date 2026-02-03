<?php

namespace App\Services\Notifications;

use App\Services\Notifications\DTOs\DeliveryReceiptDTO;

interface DeliveryReceiptHandlerInterface
{
    /**
     * @param  array<string, array<int, string>>  $headers
     */
    public function verify(array $headers, string $rawBody): void;

    public function parse(string $rawBody): DeliveryReceiptDTO;
}
