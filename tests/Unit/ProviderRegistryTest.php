<?php

use App\Services\Notifications\DTOs\SendRequest;
use App\Services\Notifications\DTOs\SendResult;
use App\Services\Notifications\NotificationProviderInterface;
use App\Services\Notifications\ProviderRegistry;

it('resolves a registered provider', function () {
    $registry = new ProviderRegistry();

    $provider = new class implements NotificationProviderInterface {
        public function send(SendRequest $request): SendResult
        {
            return new SendResult(true, 'id', 202);
        }

        public function name(): string
        {
            return 'fake';
        }
    };

    $registry->register('sms', $provider);
    $registry->registerProvider('fake', $provider);

    expect($registry->resolve('sms'))->toBe($provider);
    expect($registry->resolveProvider('fake'))->toBe($provider);
});

it('throws when provider is missing', function () {
    $registry = new ProviderRegistry();

    $registry->resolve('sms');
})->throws(RuntimeException::class);
