<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Notification',
    type: 'object',
    required: [
        'id',
        'batch_id',
        'to',
        'channel',
        'priority',
        'status',
        'correlation_id',
        'created_at',
        'updated_at',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'batch_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'template_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'to', type: 'string'),
        new OA\Property(property: 'channel', type: 'string', enum: ['sms', 'email', 'push']),
        new OA\Property(property: 'content', type: 'string', nullable: true),
        new OA\Property(property: 'variables', type: 'object', additionalProperties: true, nullable: true),
        new OA\Property(property: 'priority', type: 'string', enum: ['high', 'normal', 'low']),
        new OA\Property(property: 'status', type: 'string', enum: [
            'pending',
            'queued',
            'sending',
            'accepted',
            'delivered',
            'failed',
            'canceled',
            'scheduled',
            'unknown',
        ]),
        new OA\Property(property: 'provider_message_id', type: 'string', nullable: true),
        new OA\Property(property: 'attempts', type: 'integer'),
        new OA\Property(property: 'last_error', type: 'string', nullable: true),
        new OA\Property(property: 'correlation_id', type: 'string'),
        new OA\Property(property: 'scheduled_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'accepted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'delivered_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'last_status_check_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'next_status_check_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
/**
 * @mixin \App\Models\Notification
 */
class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Notification $notification */
        $notification = $this->resource;

        return [
            'id' => $notification->id,
            'batch_id' => $notification->batch_id,
            'template_id' => $notification->template_id,
            'to' => $notification->to,
            'channel' => $notification->channel->value,
            'content' => $notification->content,
            'variables' => $notification->variables,
            'priority' => $notification->priority->value,
            'status' => $notification->status->value,
            'provider_message_id' => $notification->provider_message_id,
            'attempts' => $notification->attempts,
            'last_error' => $notification->last_error,
            'correlation_id' => $notification->correlation_id,
            'scheduled_at' => $notification->scheduled_at?->toISOString(),
            'accepted_at' => $notification->accepted_at?->toISOString(),
            'delivered_at' => $notification->delivered_at?->toISOString(),
            'last_status_check_at' => $notification->last_status_check_at?->toISOString(),
            'next_status_check_at' => $notification->next_status_check_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
            'updated_at' => $notification->updated_at?->toISOString(),
        ];
    }
}
