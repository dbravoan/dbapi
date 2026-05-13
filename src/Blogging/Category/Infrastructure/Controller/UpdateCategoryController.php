<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Infrastructure\Controller;

use Dbapi\Blogging\Category\Application\Update\UpdateCategoryCommand;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class UpdateCategoryController extends ApiController
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Put(
        path: "/{tenant}/{version}/categories/{id}",
        summary: "Actualizar una categoría",
        security: [["bearerAuth" => []]],
        tags: ["Blogging - Categories"],
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
                    new OA\Property(property: "name", type: "string", maxLength: 255, example: "Nuevo nombre"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Categoría actualizada", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Error de validación", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $command = new UpdateCategoryCommand(
            $id,
            $validated['name']
        );

        $this->bus->dispatch($command);

        return $this->sendResponse(null, 'Category updated successfully');
    }
}
