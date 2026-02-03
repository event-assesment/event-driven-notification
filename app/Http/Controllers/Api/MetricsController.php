<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Queue;
use OpenApi\Attributes as OA;

class MetricsController extends Controller
{
    #[OA\Get(
        path: '/api/metrics',
        summary: 'Get recent notification metrics',
        tags: ['Metrics'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Metrics snapshot.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'queue_depth', type: 'integer'),
                        new OA\Property(property: 'success_last_minute', type: 'integer'),
                        new OA\Property(property: 'failed_last_minute', type: 'integer'),
                        new OA\Property(property: 'avg_latency_ms', type: 'integer'),
                    ]
                )
            ),
        ],
    )]
    public function index(): JsonResponse
    {
        $oneMinuteAgo = now()->subMinute();
        $queues = array_unique(array_values((array) config('notifications.queues', [])));

        $queueDepth = 0;

        foreach ($queues as $queue) {
            $queueDepth += Queue::size($queue);
        }

        $successLastMinute = Notification::query()
            ->where('status', NotificationStatus::Delivered)
            ->where('updated_at', '>=', $oneMinuteAgo)
            ->count();

        $failedLastMinute = Notification::query()
            ->where('status', NotificationStatus::Failed)
            ->where('updated_at', '>=', $oneMinuteAgo)
            ->count();

        $avgLatency = Notification::query()
            ->where('status', NotificationStatus::Delivered)
            ->whereNotNull('updated_at')
            ->where('updated_at', '>=', $oneMinuteAgo)
            ->selectRaw('AVG(TIMESTAMPDIFF(MICROSECOND, created_at, updated_at)) as avg_latency')
            ->value('avg_latency');

        $avgLatencyMs = $avgLatency ? (int) round(((float) $avgLatency) / 1000) : 0;

        return response()->json([
            'queue_depth' => $queueDepth,
            'success_last_minute' => $successLastMinute,
            'failed_last_minute' => $failedLastMinute,
            'avg_latency_ms' => $avgLatencyMs,
        ]);
    }
}
