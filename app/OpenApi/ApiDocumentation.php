<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Event-driven Notification API',
    version: '1.0.0',
    description: 'API for creating, tracking, and validating notifications.',
)]
#[OA\Tag(
    name: 'Notifications',
    description: 'Notification lifecycle endpoints.',
)]
#[OA\Tag(
    name: 'Templates',
    description: 'Template management and validation endpoints.',
)]
#[OA\Tag(
    name: 'Providers',
    description: 'Provider callback endpoints.',
)]
#[OA\Tag(
    name: 'Metrics',
    description: 'Operational metrics endpoints.',
)]
#[OA\Tag(
    name: 'Health',
    description: 'Service health endpoint.',
)]
final class ApiDocumentation
{
}
