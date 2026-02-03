<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Events\NotificationStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\BatchStatusRequest;
use App\Http\Requests\CancelNotificationRequest;
use App\Http\Requests\ListNotificationsRequest;
use App\Http\Requests\StoreBatchNotificationRequest;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\Template;
use App\Services\Templates\TemplateSafetyValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use function event;

class NotificationController extends Controller
{
    #[OA\Get(
        path: '/api/notifications',
        summary: 'List notifications',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: [
                    'pending',
                    'queued',
                    'sending',
                    'accepted',
                    'delivered',
                    'failed',
                    'canceled',
                    'scheduled',
                    'unknown',
                ])
            ),
            new OA\Parameter(
                name: 'channel',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['sms', 'email', 'push'])
            ),
            new OA\Parameter(
                name: 'priority',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['high', 'normal', 'low'])
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: 'created_from',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date-time')
            ),
            new OA\Parameter(
                name: 'created_to',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date-time')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paged notifications list.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Notification')
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors', type: 'object', additionalProperties: true),
                    ]
                )
            ),
        ],
    )]
    public function index(ListNotificationsRequest $request): JsonResponse
    {
        $query = Notification::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('created_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->input('created_from')));
        }

        if ($request->filled('created_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->input('created_to')));
        }

        $perPage = (int) $request->input('per_page', 15);

        $notifications = $query->latest()->paginate($perPage);

        return NotificationResource::collection($notifications)->response();
    }

    #[OA\Get(
        path: '/api/notifications/{notification}',
        summary: 'Get notification by id',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'notification',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification details.',
                content: new OA\JsonContent(ref: '#/components/schemas/Notification')
            ),
            new OA\Response(
                response: 404,
                description: 'Notification not found.'
            ),
        ],
    )]
    public function show(Notification $notification): JsonResponse
    {
        return (new NotificationResource($notification))->response();
    }

    #[OA\Get(
        path: '/api/notifications/batch/{batchId}',
        summary: 'List notifications for a batch',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'batchId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Batch notifications.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Notification')
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.'
            ),
        ],
    )]
    public function batch(BatchStatusRequest $request): JsonResponse
    {
        $batchId = $request->input('batch_id');

        $notifications = Notification::query()
            ->where('batch_id', $batchId)
            ->latest()
            ->get();

        return NotificationResource::collection($notifications)->response();
    }

    #[OA\Post(
        path: '/api/notifications',
        summary: 'Create a notification',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'Idempotency-Key',
                in: 'header',
                required: false,
                description: 'Optional key to ensure idempotent requests.',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreNotificationRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Notification created.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'batch_id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'status', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.'
            ),
        ],
    )]
    public function store(StoreNotificationRequest $request, TemplateSafetyValidator $validator): JsonResponse
    {
        $payload = $request->validated();
        $idempotencyKey = $this->resolveIdempotencyKey($request);
        $existing = $idempotencyKey === null
            ? null
            : Notification::query()
                ->where('idempotency_key', $idempotencyKey)
                ->get();

        if ($existing !== null && $existing->isNotEmpty()) {
            if ($existing->count() !== 1 || ! $this->payloadMatchesNotification($payload, $existing->first())) {
                return response()->json([
                    'message' => 'Idempotency-Key reuse with different payload.',
                ], 409);
            }

            $notification = $existing->first();

            return response()->json([
                'id' => $notification->id,
                'batch_id' => $notification->batch_id,
                'status' => $notification->status->value,
            ], 201);
        }

        $batchId = (string) Str::uuid();
        $correlationId = (string) $request->attributes->get('correlation_id');

        if ($correlationId === '') {
            $correlationId = (string) Str::uuid();
        }

        $templateErrors = $this->validateTemplatePayload($payload, $validator);

        if ($templateErrors !== []) {
            return response()->json(['errors' => $templateErrors], 422);
        }

        $notification = $this->createNotification($payload, $batchId, $correlationId, $idempotencyKey);

        if ($notification->wasRecentlyCreated) {
            $this->dispatchNotification($notification);
            event(new NotificationStatusChanged($notification));
        }

        return response()->json([
            'id' => $notification->id,
            'batch_id' => $notification->batch_id,
            'status' => $notification->status->value,
        ], 201);
    }

    #[OA\Post(
        path: '/api/notifications/batch',
        summary: 'Create a batch of notifications',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'Idempotency-Key',
                in: 'header',
                required: false,
                description: 'Optional key to ensure idempotent batch requests.',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreBatchNotificationRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Batch created.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'batch_id', type: 'string', format: 'uuid'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Notification')
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.'
            ),
        ],
    )]
    public function storeBatch(StoreBatchNotificationRequest $request, TemplateSafetyValidator $validator): JsonResponse
    {
        $payload = $request->validated();
        $idempotencyKey = $this->resolveIdempotencyKey($request);
        $correlationId = (string) $request->attributes->get('correlation_id');

        if ($correlationId === '') {
            $correlationId = (string) Str::uuid();
        }
        $notificationsPayload = $payload['notifications'];

        if ($idempotencyKey !== null) {
            $existing = Notification::query()
                ->where('idempotency_key', $idempotencyKey)
                ->get();

            if ($existing->isNotEmpty()) {
                if (!$this->batchPayloadMatchesNotifications($notificationsPayload, $existing)) {
                    return response()->json([
                        'message' => 'Idempotency-Key reuse with different payload.',
                    ], 409);
                }

                return NotificationResource::collection($existing)
                    ->additional(['batch_id' => $existing->first()->batch_id])
                    ->response()
                    ->setStatusCode(201);
            }
        }

        $batchId = (string) Str::uuid();

        $templateIds = collect($notificationsPayload)
            ->pluck('template_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $templatesById = $templateIds === []
            ? []
            : Template::query()
                ->whereIn('id', $templateIds)
                ->get()
                ->keyBy('id')
                ->all();

        $errors = [];

        foreach ($notificationsPayload as $index => $notificationPayload) {
            $templateErrors = $this->validateTemplatePayload($notificationPayload, $validator, $templatesById);

            if ($templateErrors !== []) {
                $errors[$index] = $templateErrors;
            }
        }

        if ($errors !== []) {
            return response()->json(['errors' => $errors], 422);
        }

        $created = [];

        foreach ($notificationsPayload as $notificationPayload) {
            $notification = $this->createNotification($notificationPayload, $batchId, $correlationId, $idempotencyKey);
            $created[] = $notification;

            if ($notification->wasRecentlyCreated) {
                $this->dispatchNotification($notification);
                event(new NotificationStatusChanged($notification));
            }
        }

        return NotificationResource::collection($created)
            ->additional(['batch_id' => $batchId])
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Post(
        path: '/api/notifications/{notification}/cancel',
        summary: 'Cancel a queued notification',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'notification',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification canceled.',
                content: new OA\JsonContent(ref: '#/components/schemas/Notification')
            ),
            new OA\Response(
                response: 404,
                description: 'Notification not found.'
            ),
            new OA\Response(
                response: 422,
                description: 'Notification cannot be canceled.'
            ),
        ],
    )]
    public function cancel(CancelNotificationRequest $request, Notification $notification): JsonResponse
    {
        if (!in_array($notification->status, [NotificationStatus::Pending, NotificationStatus::Queued], true)) {
            return response()->json([
                'message' => 'Notification cannot be canceled in its current status.',
            ], 422);
        }

        $notification->status = NotificationStatus::Canceled;
        $notification->save();

        event(new NotificationStatusChanged($notification));

        return (new NotificationResource($notification))->response();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, Template>  $templatesById
     * @return array<int, string>
     */
    private function validateTemplatePayload(array $payload, TemplateSafetyValidator $validator, array $templatesById = []): array
    {
        $templateId = $payload['template_id'] ?? null;

        if ($templateId === null) {
            return [];
        }

        $template = $templatesById[$templateId] ?? Template::query()->find($templateId);

        if (!$template instanceof Template) {
            return ['Template not found.'];
        }

        if (($payload['channel'] ?? null) !== $template->channel->value) {
            return ['Template channel does not match notification channel.'];
        }

        return $validator->validate($template->body);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createNotification(
        array $payload,
        string $batchId,
        string $correlationId,
        ?string $idempotencyKey
    ): Notification {
        $scheduledAt = isset($payload['scheduled_at']) ? Carbon::parse($payload['scheduled_at']) : null;
        $status = $scheduledAt?->isFuture() ? NotificationStatus::Scheduled : NotificationStatus::Queued;
        $priority = isset($payload['priority'])
            ? NotificationPriority::from($payload['priority'])
            : NotificationPriority::Normal;

        $attributes = [
            'batch_id' => $batchId,
            'to' => $payload['to'],
            'channel' => $payload['channel'],
        ];

        $hasTemplate = array_key_exists('template_id', $payload) && $payload['template_id'] !== null;

        $values = [
            'idempotency_key' => $idempotencyKey,
            'template_id' => $payload['template_id'] ?? null,
            'content' => $hasTemplate ? null : $payload['content'],
            'variables' => $hasTemplate ? ($payload['variables'] ?? []) : null,
            'priority' => $priority,
            'status' => $status,
            'correlation_id' => $correlationId,
            'scheduled_at' => $scheduledAt,
        ];

        return Notification::query()->firstOrCreate($attributes, $values);
    }

    private function dispatchNotification(Notification $notification): void
    {
        $queue = $this->queueForPriority($notification->priority->value);

        $job = (new SendNotificationJob($notification))->onQueue($queue);

        if ($notification->status === NotificationStatus::Scheduled && $notification->scheduled_at !== null) {
            $job->delay($notification->scheduled_at);
        }

        dispatch($job);
    }

    private function queueForPriority(string $priority): string
    {
        return match ($priority) {
            'high' => (string) config('notifications.queues.high'),
            'low' => (string) config('notifications.queues.low'),
            default => (string) config('notifications.queues.normal'),
        };
    }

    private function resolveIdempotencyKey(Request $request): ?string
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!is_string($idempotencyKey)) {
            return null;
        }

        $idempotencyKey = trim($idempotencyKey);

        if ($idempotencyKey === '') {
            return null;
        }

        if (mb_strlen($idempotencyKey) > 255) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Idempotency-Key must be 255 characters or fewer.',
            ]);
        }

        return $idempotencyKey;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadMatchesNotification(array $payload, Notification $notification): bool
    {
        $hasTemplate = array_key_exists('template_id', $payload) && $payload['template_id'] !== null;
        $expectedContent = $hasTemplate ? null : $payload['content'];
        $expectedVariables = $hasTemplate ? ($payload['variables'] ?? []) : null;
        $expectedPriority = isset($payload['priority'])
            ? NotificationPriority::from($payload['priority'])->value
            : NotificationPriority::Normal->value;
        $expectedScheduledAt = isset($payload['scheduled_at'])
            ? Carbon::parse($payload['scheduled_at'])->toISOString()
            : null;

        return $notification->to === $payload['to']
            && $notification->channel->value === $payload['channel']
            && $notification->template_id === ($payload['template_id'] ?? null)
            && $notification->content === $expectedContent
            && $notification->variables == $expectedVariables
            && $notification->priority->value === $expectedPriority
            && $notification->scheduled_at?->toISOString() === $expectedScheduledAt;
    }

    /**
     * @param  array<int, array<string, mixed>>  $notificationsPayload
     * @param  \Illuminate\Support\Collection<int, Notification>  $existing
     */
    private function batchPayloadMatchesNotifications(array $notificationsPayload, \Illuminate\Support\Collection $existing): bool
    {
        if (count($notificationsPayload) !== $existing->count()) {
            return false;
        }

        $existingByKey = [];

        foreach ($existing as $notification) {
            $key = $this->notificationComparisonKey($notification->to, $notification->channel->value);
            $existingByKey[$key] = $notification;
        }

        foreach ($notificationsPayload as $payload) {
            $key = $this->notificationComparisonKey((string) $payload['to'], (string) $payload['channel']);

            if (!array_key_exists($key, $existingByKey)) {
                return false;
            }

            $notification = $existingByKey[$key];
            unset($existingByKey[$key]);

            if (!$this->payloadMatchesNotification($payload, $notification)) {
                return false;
            }
        }

        return $existingByKey === [];
    }

    private function notificationComparisonKey(string $to, string $channel): string
    {
        return "{$to}|{$channel}";
    }
}
