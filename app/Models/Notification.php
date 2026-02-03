<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationFactory> */
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'batch_id',
        'idempotency_key',
        'template_id',
        'to',
        'channel',
        'content',
        'variables',
        'priority',
        'status',
        'provider_message_id',
        'attempts',
        'last_error',
        'correlation_id',
        'scheduled_at',
        'accepted_at',
        'delivered_at',
        'last_status_check_at',
        'next_status_check_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'priority' => NotificationPriority::class,
            'status' => NotificationStatus::class,
            'scheduled_at' => 'datetime',
            'variables' => 'array',
            'accepted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'last_status_check_at' => 'datetime',
            'next_status_check_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
