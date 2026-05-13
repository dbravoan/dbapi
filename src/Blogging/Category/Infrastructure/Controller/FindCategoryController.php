<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Infrastructure\Controller;

use Dbapi\Blogging\Category\Application\Find\FindCategoryQuery;
use Dbapi\Blogging\Category\Application\Response\CategoryResponse;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class FindCategoryController extends ApiController
{
    public function __construct(private readonly QueryBus $bus) {}

    #[OA\Get(
        path: "/{tenant}/{version}/categories/{id}",
        summary: "Obtener una categoría por ID",
        tags: ["Blogging - Categories"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string", format: "uuid")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Categoría encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/CategoryResponse"),
                        new OA\Property(property: "message", type: "string"),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "No encontrada", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $id = (string) $request->route('id');

        /** @var CategoryResponse|null $response */
        $response = $this->bus->ask(new FindCategoryQuery($id));

        if (null === $response) {
            return $this->sendError('Not Found', [], 404);
        }

        return $this->sendResponse($response->toArray(), 'Category found successfully');
    }
}
