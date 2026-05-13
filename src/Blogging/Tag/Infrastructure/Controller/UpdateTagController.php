<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Infrastructure\Controller;

use Dbapi\Blogging\Tag\Application\Update\UpdateTagCommand;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class UpdateTagController extends ApiController
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Put(
        path: "/{tenant}/{version}/tags/{id}",
        summary: "Actualizar un tag",
        security: [["bearerAuth" => []]],
        tags: ["Blogging - Tags"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string", format: "uuid")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", maxLength: 255, example: "php8"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Tag actualizado", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Error de validación", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $command = new UpdateTagCommand(
            $id,
            $validated['name']
        );

        $this->bus->dispatch($command);

        return $this->sendResponse(null, 'Tag updated successfully');
    }
}
