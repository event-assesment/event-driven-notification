<?php

namespace App\Services\Notifications;

use App\Services\Notifications\DTOs\ProviderStatusDTO;

interface StatusQueryableProviderInterface
{
    public function fetchStatus(string $providerMessageId): ProviderStatusDTO;
}
