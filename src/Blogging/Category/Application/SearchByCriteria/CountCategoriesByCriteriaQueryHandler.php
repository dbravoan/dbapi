<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Application\SearchByCriteria;

use Dbapi\Blogging\Category\Domain\CategoryRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Domain\Criteria\Filters;
use Dba\DddSkeleton\Shared\Domain\Criteria\Order;

final class CountCategoriesByCriteriaQueryHandler implements QueryHandler
{
    public function __construct(private readonly CategoryRepository $repository) {}

    public function __invoke(CountCategoriesByCriteriaQuery $query): CountCategoriesByCriteriaResponse
    {
        $criteria = new Criteria(
            Filters::fromValues($query->filters()),
            Order::fromValues($query->orderBy(), $query->orderType()),
            null,
            null,
        );

        return new CountCategoriesByCriteriaResponse(
            $this->repository->countByCriteria($criteria),
        );
    }
}
