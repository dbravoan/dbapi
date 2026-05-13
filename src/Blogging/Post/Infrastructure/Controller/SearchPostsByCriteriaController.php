<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Infrastructure\Controller;

use Dbapi\Blogging\Post\Application\Response\PostsResponse;
use Dbapi\Blogging\Post\Application\SearchByCriteria\CountPostsByCriteriaQuery;
use Dbapi\Blogging\Post\Application\SearchByCriteria\CountPostsByCriteriaResponse;
use Dbapi\Blogging\Post\Application\SearchByCriteria\SearchPostsByCriteriaQuery;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Dba\DddSkeleton\Shared\Infrastructure\Criteria\RequestCriteriaBuilder;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class SearchPostsByCriteriaController extends ApiController
{
    public function __construct(
        private readonly QueryBus $bus,
        private readonly RequestCriteriaBuilder $requestCriteriaBuilder,
    ) {}

    #[OA\Get(
        path: "/{tenant}/{version}/posts",
        summary: "List posts (filterable, paginated)",
        tags: ["Blogging - Posts"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "language_code", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "en"),
            new OA\Parameter(name: "filters", in: "query", required: false, schema: new OA\Schema(type: "array", items: new OA\Items(type: "object"))),
            new OA\Parameter(name: "order_by", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["asc", "desc"])),
            new OA\Parameter(name: "limit", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "offset", in: "query", required: false, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "List of posts"),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $criteria = $this->requestCriteriaBuilder->build($request);
        $offset = $criteria->offset() ?? 0;
        $limit = $criteria->limit();

        $filters = (array) $request->get('filters', []);
        $languageCode = (string) $request->get('language_code', 'en');
        $orderBy = $criteria->order()->orderBy()->value();
        $orderType = $criteria->order()->orderType()->value();

        /** @var PostsResponse $postsResponse */
        $postsResponse = $this->bus->ask(new SearchPostsByCriteriaQuery(
            $filters,
            $orderBy !== '' ? $orderBy : null,
            $orderType !== '' ? $orderType : null,
            $limit,
            $offset,
            $languageCode,
        ));

        /** @var CountPostsByCriteriaResponse $filteredResp */
        $filteredResp = $this->bus->ask(new CountPostsByCriteriaQuery($filters));
        /** @var CountPostsByCriteriaResponse $totalResp */
        $totalResp = $this->bus->ask(new CountPostsByCriteriaQuery([]));

        $filteredRecords = $filteredResp->count();
        $totalRecords = $totalResp->count();
        $currentPage = $limit ? (int) floor($offset / $limit) + 1 : 1;
        $totalPages = $limit ? (int) ceil($filteredRecords / $limit) : 1;

        return $this->sendResponse([
            'data' => $postsResponse->toArray(),
            'meta' => [
                'current_page'     => $currentPage,
                'total_pages'      => $totalPages,
                'filtered_records' => $filteredRecords,
                'total_records'    => $totalRecords,
                'per_page'         => $limit,
            ],
        ], 'Posts searched successfully');
    }
}
