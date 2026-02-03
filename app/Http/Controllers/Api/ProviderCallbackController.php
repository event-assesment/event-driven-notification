<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationStatus;
use App\Events\NotificationStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderCallbackRequest;
use App\Models\Notification;
use App\Services\Notifications\DeliveryReceiptHandlerInterface;
use App\Services\Notifications\ProviderRegistry;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use RuntimeException;
use function event;

class ProviderCallbackController extends Controller
{
    #[OA\Post(
        path: '/api/providers/{provider}/callbacks',
        summary: 'Handle provider delivery callbacks',
        tags: ['Providers'],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'object')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Callback accepted.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Callback verification failed.'
            ),
            new OA\Response(
                response: 404,
                description: 'Provider or notification not found.'
            ),
            new OA\Response(
                response: 422,
                description: 'Unsupported receipt status.'
            ),
        ],
    )]
    public function store(ProviderCallbackRequest $request, ProviderRegistry $registry): JsonResponse
    {
        $providerName = (string) $request->input('provider');

        try {
            $provider = $registry->resolveProvider($providerName);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        if (!$provider instanceof DeliveryReceiptHandlerInterface) {
            return response()->json(['message' => 'Provider does not support delivery receipts.'], 422);
        }

        $rawBody = $request->getContent();

        try {
            $provider->verify($request->headers->all(), $rawBody);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        $receipt = $provider->parse($rawBody);

        $notification = Notification::query()
            ->where('provider_message_id', $receipt->messageId)
            ->first();

        if (!$notification instanceof Notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        if ($receipt->status === 'delivered') {
            $notification->status = NotificationStatus::Delivered;
            $notification->delivered_at = $receipt->timestamp ?? now();
            $notification->last_error = null;
        } elseif ($receipt->status === 'failed') {
            $notification->status = NotificationStatus::Failed;
            $notification->last_error = $receipt->errorMessage ?? $receipt->errorCode;
        } else {
            return response()->json(['message' => 'Unsupported receipt status.'], 422);
        }

        $notification->last_status_check_at = now();
        $notification->next_status_check_at = null;
        $notification->save();

        event(new NotificationStatusChanged($notification));

        return response()->json(['status' => $notification->status->value]);
    }
}
