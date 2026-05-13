<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Infrastructure\Controller;

use Dbapi\Blogging\Post\Application\Find\FindPostQuery;
use Dbapi\Blogging\Post\Application\Response\PostResponse;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class FindPostController extends ApiController
{
    public function __construct(private readonly QueryBus $bus) {}

    #[OA\Get(
        path: "/{tenant}/{version}/posts/{id}",
        summary: "Obtener un post por ID",
        tags: ["Blogging - Posts"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string", format: "uuid")),
            new OA\Parameter(name: "lang", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "es"),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Post encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/PostResponse"),
                        new OA\Property(property: "message", type: "string"),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Post no encontrado", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $id = (string) $request->route('id');
        $languageCode = (string) ($request->query('lang') ?? 'en');

        /** @var PostResponse|null $response */
        $response = $this->bus->ask(new FindPostQuery($id, $languageCode));

        if (null === $response) {
            return $this->sendError('Not Found', [], 404);
        }

        return $this->sendResponse($response->toArray(), 'Post found successfully');
    }
}
