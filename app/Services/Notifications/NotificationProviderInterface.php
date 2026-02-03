<?php

namespace App\Services\Notifications;

use App\Services\Notifications\DTOs\SendRequest;
use App\Services\Notifications\DTOs\SendResult;

interface NotificationProviderInterface
{
    public function send(SendRequest $request): SendResult;

    public function name(): string;
}
