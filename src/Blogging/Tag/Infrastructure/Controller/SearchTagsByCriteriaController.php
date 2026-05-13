<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Infrastructure\Controller;

use Dbapi\Blogging\Tag\Application\Response\TagsResponse;
use Dbapi\Blogging\Tag\Application\SearchByCriteria\CountTagsByCriteriaQuery;
use Dbapi\Blogging\Tag\Application\SearchByCriteria\CountTagsByCriteriaResponse;
use Dbapi\Blogging\Tag\Application\SearchByCriteria\SearchTagsByCriteriaQuery;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Dba\DddSkeleton\Shared\Infrastructure\Criteria\RequestCriteriaBuilder;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class SearchTagsByCriteriaController extends ApiController
{
    public function __construct(
        private readonly QueryBus $bus,
        private readonly RequestCriteriaBuilder $requestCriteriaBuilder,
    ) {}

    #[OA\Get(
        path: "/{tenant}/{version}/tags",
        summary: "List tags (filterable, paginated)",
        tags: ["Blogging - Tags"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filters", in: "query", required: false, schema: new OA\Schema(type: "array", items: new OA\Items(type: "object"))),
            new OA\Parameter(name: "order_by", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["asc", "desc"])),
            new OA\Parameter(name: "limit", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "offset", in: "query", required: false, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "List of tags"),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $criteria = $this->requestCriteriaBuilder->build($request);
        $offset = $criteria->offset() ?? 0;
        $limit = $criteria->limit();

        $filters = (array) $request->get('filters', []);
        $orderBy = $criteria->order()->orderBy()->value();
        $orderType = $criteria->order()->orderType()->value();

        /** @var TagsResponse $tagsResponse */
        $tagsResponse = $this->bus->ask(new SearchTagsByCriteriaQuery(
            $filters,
            $orderBy !== '' ? $orderBy : null,
            $orderType !== '' ? $orderType : null,
            $limit,
            $offset,
        ));

        /** @var CountTagsByCriteriaResponse $filteredResp */
        $filteredResp = $this->bus->ask(new CountTagsByCriteriaQuery($filters));
        /** @var CountTagsByCriteriaResponse $totalResp */
        $totalResp = $this->bus->ask(new CountTagsByCriteriaQuery([]));

        $filteredRecords = $filteredResp->count();
        $totalRecords = $totalResp->count();
        $currentPage = $limit ? (int) floor($offset / $limit) + 1 : 1;
        $totalPages = $limit ? (int) ceil($filteredRecords / $limit) : 1;

        return $this->sendResponse([
            'data' => $tagsResponse->toArray(),
            'meta' => [
                'current_page'     => $currentPage,
                'total_pages'      => $totalPages,
                'filtered_records' => $filteredRecords,
                'total_records'    => $totalRecords,
                'per_page'         => $limit,
            ],
        ], 'Tags searched successfully');
    }
}
