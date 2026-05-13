<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Application\SearchByCriteria;

use Dbapi\Blogging\Tag\Application\Response\TagResponse;
use Dbapi\Blogging\Tag\Application\Response\TagsResponse;
use Dbapi\Blogging\Tag\Domain\Tag;
use Dbapi\Blogging\Tag\Domain\TagRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Domain\Criteria\Filters;
use Dba\DddSkeleton\Shared\Domain\Criteria\Order;

final class SearchTagsByCriteriaQueryHandler implements QueryHandler
{
    public function __construct(private readonly TagRepository $repository) {}

    public function __invoke(SearchTagsByCriteriaQuery $query): TagsResponse
    {
        $criteria = new Criteria(
            Filters::fromValues($query->filters()),
            Order::fromValues($query->orderBy(), $query->orderType()),
            $query->offset(),
            $query->limit(),
        );

        $entities = $this->repository->searchByCriteria($criteria);

        $responses = array_map(
            static fn (Tag $tag) => TagResponse::fromAggregate($tag),
            $entities,
        );

        return new TagsResponse(...$responses);
    }
}
