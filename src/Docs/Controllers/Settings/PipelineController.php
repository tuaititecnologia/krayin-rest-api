<?php

namespace Webkul\RestApi\Docs\Controllers\Settings;

use OpenApi\Attributes as OA;

class PipelineController
{
    #[OA\Get(
        path: '/api/v1/settings/pipelines',
        operationId: 'getPipelines',
        tags: ['Pipeline'],
        summary: 'Get all pipelines',
        security: [['sanctum_admin' => []]],
        parameters: [
            new OA\Parameter(
                name: 'sort',
                description: 'Sort column',
                example: 'id',
                required: false,
                in: 'query',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'order',
                description: 'Sort order',
                required: false,
                in: 'query',
                schema: new OA\Schema(type: 'string', enum: ['desc', 'asc'])
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number',
                required: false,
                in: 'query',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Limit',
                in: 'query',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pipelines fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Pipeline')
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
        ]
    )]
    public function index() {}

    #[OA\Post(
        path: '/api/v1/settings/pipelines',
        operationId: 'createPipeline',
        tags: ['Pipeline'],
        summary: 'Create a new pipeline',
        security: [['sanctum_admin' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Pipeline details',
            content: new OA\JsonContent(
                required: ['name', 'rotten_days', 'is_default', 'stages'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        description: 'Name of the pipeline',
                        example: 'Test'
                    ),
                    new OA\Property(
                        property: 'rotten_days',
                        type: 'integer',
                        description: 'Number of days after which the pipeline is considered rotten',
                        example: 30
                    ),
                    new OA\Property(
                        property: 'is_default',
                        type: 'string',
                        description: 'Indicates if the pipeline is the default one',
                        example: 'on'
                    ),
                    new OA\Property(
                        property: 'stages',
                        type: 'object',
                        description: 'Stages of the pipeline',
                        properties: [
                            new OA\Property(
                                property: 'stage_1',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'code', type: 'string', example: 'new'),
                                    new OA\Property(property: 'name', type: 'string', example: 'New'),
                                    new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                                    new OA\Property(property: 'probability', type: 'integer', example: 100),
                                ]
                            ),
                            new OA\Property(
                                property: 'stage_2',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'code', type: 'string', example: 'test'),
                                    new OA\Property(property: 'name', type: 'string', example: 'test'),
                                    new OA\Property(property: 'sort_order', type: 'integer', example: 2),
                                    new OA\Property(property: 'probability', type: 'integer', example: 100),
                                ]
                            ),
                            new OA\Property(
                                property: 'stage_99',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'code', type: 'string', example: 'won'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Won'),
                                    new OA\Property(property: 'sort_order', type: 'integer', example: 3),
                                    new OA\Property(property: 'probability', type: 'integer', example: 100),
                                ]
                            ),
                            new OA\Property(
                                property: 'stage_100',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'code', type: 'string', example: 'lost'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Lost'),
                                    new OA\Property(property: 'sort_order', type: 'integer', example: 4),
                                    new OA\Property(property: 'probability', type: 'integer', example: 0),
                                ]
                            ),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pipeline created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            ref: '#/components/schemas/Pipeline'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
        ]
    )]
    public function store() {}

    #[OA\Put(
        path: '/api/v1/settings/pipelines/{id}',
        operationId: 'updatePipeline',
        tags: ['Pipeline'],
        summary: 'Update a pipeline',
        security: [['sanctum_admin' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID of the pipeline',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Pipeline details',
            content: new OA\JsonContent(
                required: ['name', 'rotten_days', 'is_default', 'stages'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        description: 'Name of the pipeline',
                        example: 'Test'
                    ),
                    new OA\Property(
                        property: 'rotten_days',
                        type: 'integer',
                        description: 'Number of days after which the pipeline is considered rotten',
                        example: 30
                    ),
                    new OA\Property(
                        property: 'is_default',
                        type: 'string',
                        description: 'Indicates if the pipeline is the default one',
                        example: 'on'
                    ),
                    new OA\Property(
                        property: 'stages',
                        type: 'object',
                        description: 'Stages of the pipeline',
                        properties: [
                            new OA\Property(
                                property: '7',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'code', type: 'string', example: 'new'),
                                    new OA\Property(property: 'name', type: 'string', example: 'New'),
                                    new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                                    new OA\Property(property: 'probability', type: 'integer', example: 100),
                                ]
                            ),
                            new OA\Property(
                                property: '8',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'code', type: 'string', example: 'test'),
                                    new OA\Property(property: 'name', type: 'string', example: 'test'),
                                    new OA\Property(property: 'sort_order', type: 'integer', example: 2),
                                    new OA\Property(property: 'probability', type: 'integer', example: 100),
                                ]
                            ),
                            new OA\Property(
                                property: '9',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'code', type: 'string', example: 'won'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Won'),
                                    new OA\Property(property: 'sort_order', type: 'integer', example: 3),
                                    new OA\Property(property: 'probability', type: 'integer', example: 100),
                                ]
                            ),
                            new OA\Property(
                                property: '10',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'code', type: 'string', example: 'lost'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Lost'),
                                    new OA\Property(property: 'sort_order', type: 'integer', example: 4),
                                    new OA\Property(property: 'probability', type: 'integer', example: 0),
                                ]
                            ),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pipeline updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            ref: '#/components/schemas/Pipeline'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
        ]
    )]
    public function update() {}

    #[OA\Get(
        path: '/api/v1/settings/pipelines/{id}',
        operationId: 'getPipeline',
        tags: ['Pipeline'],
        summary: 'Get a pipeline',
        security: [['sanctum_admin' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID of the pipeline',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pipeline fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            ref: '#/components/schemas/Pipeline'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
        ]
    )]
    public function show() {}

    #[OA\Delete(
        path: '/api/v1/settings/pipelines/{id}',
        operationId: 'deletePipeline',
        tags: ['Pipeline'],
        summary: 'Delete a pipeline',
        security: [['sanctum_admin' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID of the pipeline',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pipeline deleted successfully'
            ),
            new OA\Response(
                response: 400,
                description: 'Default pipeline cannot be deleted'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
        ]
    )]
    public function destroy() {}
}
