<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Infrastructure\Controller;

use Dbapi\Language\Language\Application\Find\FindLanguageQuery;
use Dbapi\Language\Language\Application\Response\LanguageResponse;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class FindLanguageController extends ApiController
{
    private QueryBus $bus;

    public function __construct(QueryBus $bus)
    {
        $this->bus = $bus;
    }

    #[OA\Get(
        path: "/{tenant}/{version}/languages/{id}",
        summary: "Get a language by ID",
        description: "Retrieve a specific language definition by its UUID.",
        tags: ["Languages"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string", format: "uuid"), description: "Language UUID"),
        ],
        responses: [
            new OA\Response(response: 200, description: "Language found", content: new OA\JsonContent(ref: "#/components/schemas/LanguageResponse")),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $id = (string) $request->route('id');

        /** @var LanguageResponse|null $response */
        $response = $this->bus->ask(new FindLanguageQuery($id));

        if (null === $response) {
            return $this->sendError('Not Found', [], 404);
        }

        return $this->sendResponse($response->toArray(), 'Language found successfully');
    }
}
