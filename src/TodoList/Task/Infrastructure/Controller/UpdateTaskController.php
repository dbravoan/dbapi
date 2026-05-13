<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Infrastructure\Controller;

use Dbapi\TodoList\Task\Application\Update\UpdateTaskCommand;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class UpdateTaskController extends ApiController
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Put(
        path: "/{tenant}/{version}/tasks/{id}",
        summary: "Actualizar una tarea",
        security: [["bearerAuth" => []]],
        tags: ["TodoList - Tasks"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string", format: "uuid")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title", "status", "priority"],
                properties: [
                    new OA\Property(property: "title", type: "string", maxLength: 255, example: "Actualizar dependencias"),
                    new OA\Property(property: "status", type: "string", enum: ["pending", "in_progress", "done"], example: "in_progress"),
                    new OA\Property(property: "priority", type: "string", enum: ["low", "medium", "high"], example: "high"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Actualizar a PHP 8.4"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Tarea actualizada", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Error de validación", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $id = (string) $request->route('id');

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'status'      => ['required', 'string', 'in:pending,in_progress,done'],
            'priority'    => ['required', 'string', 'in:low,medium,high'],
            'description' => ['nullable', 'string'],
        ]);

        $this->bus->dispatch(new UpdateTaskCommand(
            $id,
            $validated['title'],
            $validated['status'],
            $validated['priority'],
            $validated['description'] ?? null,
        ));

        return $this->sendResponse(null, 'Task updated successfully');
    }
}
