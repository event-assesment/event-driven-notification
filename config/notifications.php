<?php

return [
    'providers' => [
        'webhook_site' => [
            'endpoint' => env('WEBHOOK_SITE_URL'),
            'channels' => ['sms', 'email', 'push'],
            'timeout' => (int) env('WEBHOOK_SITE_TIMEOUT', 5),
        ],
    ],

    'channel_provider' => [
        'sms' => 'webhook_site',
        'email' => 'webhook_site',
        'push' => 'webhook_site',
    ],

    'queues' => [
        'high' => env('NOTIFICATIONS_QUEUE_HIGH', 'notifications-high'),
        'normal' => env('NOTIFICATIONS_QUEUE_NORMAL', 'notifications-normal'),
        'low' => env('NOTIFICATIONS_QUEUE_LOW', 'notifications-low'),
        'status_sync' => env('NOTIFICATIONS_STATUS_SYNC_QUEUE', 'notifications.status_sync'),
    ],

    'channels' => [
        'sms' => [
            'max_length' => 160,
        ],
        'email' => [
            'max_length' => 10000,
        ],
        'push' => [
            'max_length' => 2000,
        ],
    ],

    'rate_limits' => [
        'per_second' => (int) env('NOTIFICATIONS_RATE_LIMIT', 100),
    ],

    'status_sync' => [
        'delays' => array_map('intval', explode(',', env('NOTIFICATIONS_STATUS_SYNC_DELAYS', '5,15,60,360'))),
        'ttl_hours' => (int) env('NOTIFICATIONS_STATUS_SYNC_TTL_HOURS', 24),
    ],
];
