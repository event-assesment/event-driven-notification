<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListTemplatesRequest;
use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\TemplateValidationRequest;
use App\Http\Requests\UpdateTemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use App\Services\Templates\TemplateRenderer;
use App\Services\Templates\TemplateSafetyValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class TemplateController extends Controller
{
    #[OA\Get(
        path: '/api/templates',
        summary: 'List templates',
        tags: ['Templates'],
        parameters: [
            new OA\Parameter(
                name: 'channel',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['sms', 'email', 'push'])
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paged templates list.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Template')
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.'
            ),
        ],
    )]
    public function index(ListTemplatesRequest $request): JsonResponse
    {
        $query = Template::query();

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        $perPage = (int) $request->input('per_page', 15);

        $templates = $query->latest()->paginate($perPage);

        return TemplateResource::collection($templates)->response();
    }

    #[OA\Post(
        path: '/api/templates',
        summary: 'Create a template',
        tags: ['Templates'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreTemplateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Template created.',
                content: new OA\JsonContent(ref: '#/components/schemas/Template')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.'
            ),
        ],
    )]
    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $template = Template::query()->create($request->validated());

        return (new TemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Post(
        path: '/api/templates/validate',
        summary: 'Validate and render a template',
        tags: ['Templates'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TemplateValidationRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Template rendered.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'rendered', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.'
            ),
        ],
    )]
    public function validateTemplate(TemplateValidationRequest $request, TemplateSafetyValidator $validator, TemplateRenderer $renderer): JsonResponse
    {
        $payload = $request->validated();
        $body = $payload['body'];
        $variables = $payload['sample_variables'] ?? [];

        $errors = $validator->validate($body);

        if ($errors !== []) {
            return response()->json([
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'rendered' => $renderer->render($body, $variables),
        ]);
    }

    #[OA\Get(
        path: '/api/templates/{template}',
        summary: 'Get template by id',
        tags: ['Templates'],
        parameters: [
            new OA\Parameter(
                name: 'template',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Template details.',
                content: new OA\JsonContent(ref: '#/components/schemas/Template')
            ),
            new OA\Response(
                response: 404,
                description: 'Template not found.'
            ),
        ],
    )]
    public function show(Template $template): JsonResponse
    {
        return (new TemplateResource($template))->response();
    }

    #[OA\Patch(
        path: '/api/templates/{template}',
        summary: 'Update a template',
        tags: ['Templates'],
        parameters: [
            new OA\Parameter(
                name: 'template',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateTemplateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Template updated.',
                content: new OA\JsonContent(ref: '#/components/schemas/Template')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.'
            ),
        ],
    )]
    public function update(UpdateTemplateRequest $request, Template $template): JsonResponse
    {
        $template->fill($request->validated());
        $template->save();

        return (new TemplateResource($template))->response();
    }

    #[OA\Delete(
        path: '/api/templates/{template}',
        summary: 'Delete a template',
        tags: ['Templates'],
        parameters: [
            new OA\Parameter(
                name: 'template',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Template deleted.'
            ),
            new OA\Response(
                response: 404,
                description: 'Template not found.'
            ),
        ],
    )]
    public function destroy(Template $template): Response
    {
        $template->delete();

        return response()->noContent();
    }
}
