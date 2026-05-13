<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Infrastructure\Controller;

use Dbapi\Blogging\Category\Application\Create\CreateCategoryCommand;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class CreateCategoryController extends ApiController
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Post(
        path: "/{tenant}/{version}/categories",
        summary: "Crear una categoría",
        security: [["bearerAuth" => []]],
        tags: ["Blogging - Categories"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["id", "name"],
                properties: [
                    new OA\Property(property: "id", type: "string", format: "uuid", example: "550e8400-e29b-41d4-a716-446655440010"),
                    new OA\Property(property: "name", type: "string", maxLength: 255, example: "Tecnología"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Categoría creada", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Error de validación", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $command = new CreateCategoryCommand(
            $validated['id'],
            $validated['name']
        );

        $this->bus->dispatch($command);

        return $this->sendResponse(null, 'Category created successfully')->setStatusCode(201);
    }
}
