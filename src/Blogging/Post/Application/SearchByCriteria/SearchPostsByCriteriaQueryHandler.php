<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Application\SearchByCriteria;

use Dbapi\Blogging\Post\Application\Response\PostResponse;
use Dbapi\Blogging\Post\Application\Response\PostsResponse;
use Dbapi\Blogging\Post\Domain\Post;
use Dbapi\Blogging\Post\Domain\PostRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Domain\Criteria\Filters;
use Dba\DddSkeleton\Shared\Domain\Criteria\Order;

final class SearchPostsByCriteriaQueryHandler implements QueryHandler
{
    public function __construct(private readonly PostRepository $repository) {}

    public function __invoke(SearchPostsByCriteriaQuery $query): PostsResponse
    {
        $criteria = new Criteria(
            Filters::fromValues($query->filters()),
            Order::fromValues($query->orderBy(), $query->orderType()),
            $query->offset(),
            $query->limit(),
        );

        $entities = $this->repository->searchByCriteria($criteria, $query->languageCode());

        $responses = array_map(
            static fn (Post $post) => PostResponse::fromAggregate($post),
            $entities,
        );

        return new PostsResponse(...$responses);
    }
}
