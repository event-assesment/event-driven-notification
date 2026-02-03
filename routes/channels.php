<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('batch.{batchId}', function ($user, string $batchId): bool {
    return true;
});
