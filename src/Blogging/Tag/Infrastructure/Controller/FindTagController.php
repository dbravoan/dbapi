<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Infrastructure\Controller;

use Dbapi\Blogging\Tag\Application\Find\FindTagQuery;
use Dbapi\Blogging\Tag\Application\Response\TagResponse;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class FindTagController extends ApiController
{
    public function __construct(private readonly QueryBus $bus) {}

    #[OA\Get(
        path: "/{tenant}/{version}/tags/{id}",
        summary: "Obtener un tag por ID",
        tags: ["Blogging - Tags"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string", format: "uuid")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tag encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/TagResponse"),
                        new OA\Property(property: "message", type: "string"),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $id = (string) $request->route('id');

        /** @var TagResponse|null $response */
        $response = $this->bus->ask(new FindTagQuery($id));

        if (null === $response) {
            return $this->sendError('Not Found', [], 404);
        }

        return $this->sendResponse($response->toArray(), 'Tag found successfully');
    }
}
