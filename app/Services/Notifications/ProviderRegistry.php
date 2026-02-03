<?php

namespace App\Services\Notifications;

use RuntimeException;

class ProviderRegistry
{
    /**
     * @var array<string, NotificationProviderInterface>
     */
    private array $providersByChannel = [];

    /**
     * @var array<string, NotificationProviderInterface>
     */
    private array $providersByName = [];

    public function register(string $channel, NotificationProviderInterface $provider): void
    {
        $this->providersByChannel[$channel] = $provider;
    }

    public function registerProvider(string $name, NotificationProviderInterface $provider): void
    {
        $this->providersByName[$name] = $provider;
    }

    public function resolve(string $channel): NotificationProviderInterface
    {
        if (!isset($this->providersByChannel[$channel])) {
            throw new RuntimeException("No provider registered for channel: {$channel}");
        }

        return $this->providersByChannel[$channel];
    }

    public function resolveProvider(string $name): NotificationProviderInterface
    {
        if (!isset($this->providersByName[$name])) {
            throw new RuntimeException("No provider registered with name: {$name}");
        }

        return $this->providersByName[$name];
    }
}
