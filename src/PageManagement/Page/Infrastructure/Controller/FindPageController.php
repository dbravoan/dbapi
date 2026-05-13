<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Infrastructure\Controller;

use Dbapi\PageManagement\Page\Application\Find\FindPageQuery;
use Dbapi\PageManagement\Page\Application\Response\PageResponse;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class FindPageController extends ApiController
{
    public function __construct(private readonly QueryBus $bus) {}

    #[OA\Get(
        path: "/{tenant}/{version}/pages/{id}",
        summary: "Get a page by ID in a specific language",
        description: "Retrieve a page in the requested language. If language translation doesn't exist, returns first available translation.",
        tags: ["Pages"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string", format: "uuid")),
            new OA\Parameter(name: "lang", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "en", description: "ISO 639-1 language code (defaults to 'en')"),
        ],
        responses: [
            new OA\Response(response: 200, description: "Page found", content: new OA\JsonContent(ref: "#/components/schemas/PageResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $id           = (string) $request->route('id');
        $languageCode = (string) ($request->query('lang') ?? 'en');

        /** @var PageResponse|null $response */
        $response = $this->bus->ask(new FindPageQuery($id, $languageCode));

        if (null === $response) {
            return $this->sendError('Not Found', [], 404);
        }

        return $this->sendResponse($response->toArray(), 'Page found successfully');
    }
}
