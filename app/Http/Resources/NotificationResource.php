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
class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'template_id' => $this->template_id,
            'to' => $this->to,
            'channel' => $this->channel->value,
            'content' => $this->content,
            'variables' => $this->variables,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'provider_message_id' => $this->provider_message_id,
            'attempts' => $this->attempts,
            'last_error' => $this->last_error,
            'correlation_id' => $this->correlation_id,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'last_status_check_at' => $this->last_status_check_at?->toISOString(),
            'next_status_check_at' => $this->next_status_check_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
