<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Infrastructure\Controller;

use Dbapi\TodoList\Task\Application\Create\CreateTaskCommand;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class CreateTaskController extends ApiController
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Post(
        path: "/{tenant}/{version}/tasks",
        summary: "Crear una tarea",
        security: [["bearerAuth" => []]],
        tags: ["TodoList - Tasks"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["id", "title", "status", "priority"],
                properties: [
                    new OA\Property(property: "id", type: "string", format: "uuid", example: "550e8400-e29b-41d4-a716-446655440040"),
                    new OA\Property(property: "title", type: "string", maxLength: 255, example: "Escribir tests unitarios"),
                    new OA\Property(property: "status", type: "string", enum: ["pending", "in_progress", "done"], example: "pending"),
                    new OA\Property(property: "priority", type: "string", enum: ["low", "medium", "high"], example: "medium"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Cobertura mínima del 80%"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Tarea creada", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Error de validación", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id'          => ['required', 'uuid'],
            'title'       => ['required', 'string', 'max:255'],
            'status'      => ['required', 'string', 'in:pending,in_progress,done'],
            'priority'    => ['required', 'string', 'in:low,medium,high'],
            'description' => ['nullable', 'string'],
        ]);

        $this->bus->dispatch(new CreateTaskCommand(
            $validated['id'],
            $validated['title'],
            $validated['status'],
            $validated['priority'],
            $validated['description'] ?? null,
        ));

        return $this->sendResponse(null, 'Task created successfully')->setStatusCode(201);
    }
}
