<?php

namespace App\Providers;

use App\Services\Notifications\NotificationProviderInterface;
use App\Services\Notifications\ProviderRegistry;
use App\Services\Notifications\Providers\WebhookSiteProvider;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ProviderRegistry::class, function ($app) {
            $registry = new ProviderRegistry();
            $config = $app['config']->get('notifications', []);
            $providers = $config['providers'] ?? [];
            $channelMap = $config['channel_provider'] ?? [];

            $instances = [];

            foreach ($providers as $name => $providerConfig) {
                $instances[$name] = $this->buildProvider($name, $providerConfig);
                $registry->registerProvider($name, $instances[$name]);
            }

            foreach ($channelMap as $channel => $providerName) {
                if (!isset($instances[$providerName])) {
                    throw new RuntimeException("Provider [{$providerName}] is not configured.");
                }

                $registry->register($channel, $instances[$providerName]);
            }

            return $registry;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function buildProvider(string $name, array $config): NotificationProviderInterface
    {
        return match ($name) {
            'webhook_site' => $this->buildWebhookSiteProvider($config),
            default => throw new RuntimeException("Unsupported provider [{$name}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function buildWebhookSiteProvider(array $config): NotificationProviderInterface
    {
        $endpoint = $config['endpoint'] ?? null;

        if (!is_string($endpoint) || $endpoint === '') {
            throw new RuntimeException('WEBHOOK_SITE_URL is not configured.');
        }

        $timeout = is_int($config['timeout'] ?? null) ? $config['timeout'] : 5;

        return new WebhookSiteProvider($endpoint, $timeout);
    }
}
