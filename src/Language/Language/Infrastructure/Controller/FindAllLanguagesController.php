<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Infrastructure\Controller;

use Dbapi\Language\Language\Application\FindAll\FindAllLanguagesQuery;
use Dbapi\Language\Language\Application\FindAll\LanguageListResponse;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class FindAllLanguagesController extends ApiController
{
    private QueryBus $bus;

    public function __construct(QueryBus $bus)
    {
        $this->bus = $bus;
    }

    #[OA\Get(
        path: "/{tenant}/{version}/languages",
        summary: "List all active languages",
        description: "Get all registered and active languages for this tenant. Used for translatable content (posts, pages, etc.)",
        tags: ["Languages"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
        ],
        responses: [
            new OA\Response(response: 200, description: "List of active languages", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/LanguageResponse"))),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var LanguageListResponse $response */
        $response = $this->bus->ask(new FindAllLanguagesQuery());

        return $this->sendResponse($response->toArray(), 'Languages retrieved successfully');
    }
}
