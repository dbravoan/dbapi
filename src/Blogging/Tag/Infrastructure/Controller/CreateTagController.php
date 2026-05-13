<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Infrastructure\Controller;

use Dbapi\Blogging\Tag\Application\Create\CreateTagCommand;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class CreateTagController extends ApiController
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Post(
        path: "/{tenant}/{version}/tags",
        summary: "Crear una etiqueta",
        security: [["bearerAuth" => []]],
        tags: ["Blogging - Tags"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["id", "name", "slug"],
                properties: [
                    new OA\Property(property: "id", type: "string", format: "uuid", example: "550e8400-e29b-41d4-a716-446655440020"),
                    new OA\Property(property: "name", type: "string", maxLength: 255, example: "Laravel"),
                    new OA\Property(property: "slug", type: "string", maxLength: 255, example: "laravel"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Tag creado", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Error de validación", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
        ]);

        $command = new CreateTagCommand(
            $validated['id'],
            $validated['name'],
            $validated['slug'],
        );

        $this->bus->dispatch($command);

        return $this->sendResponse(null, 'Tag created successfully')->setStatusCode(201);
    }
}
