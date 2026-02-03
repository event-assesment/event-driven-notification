<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sending = 'sending';
    case Accepted = 'accepted';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Canceled = 'canceled';
    case Scheduled = 'scheduled';
    case Unknown = 'unknown';
}
