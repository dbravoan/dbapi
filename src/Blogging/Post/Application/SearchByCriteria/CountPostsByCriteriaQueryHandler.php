<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Application\SearchByCriteria;

use Dbapi\Blogging\Post\Domain\PostRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Domain\Criteria\Filters;
use Dba\DddSkeleton\Shared\Domain\Criteria\Order;

final class CountPostsByCriteriaQueryHandler implements QueryHandler
{
    public function __construct(private readonly PostRepository $repository) {}

    public function __invoke(CountPostsByCriteriaQuery $query): CountPostsByCriteriaResponse
    {
        $criteria = new Criteria(
            Filters::fromValues($query->filters()),
            Order::fromValues($query->orderBy(), $query->orderType()),
            null,
            null,
        );

        return new CountPostsByCriteriaResponse(
            $this->repository->countByCriteria($criteria),
        );
    }
}
