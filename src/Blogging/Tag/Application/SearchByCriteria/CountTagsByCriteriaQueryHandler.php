<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Application\SearchByCriteria;

use Dbapi\Blogging\Tag\Domain\TagRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Domain\Criteria\Filters;
use Dba\DddSkeleton\Shared\Domain\Criteria\Order;

final class CountTagsByCriteriaQueryHandler implements QueryHandler
{
    public function __construct(private readonly TagRepository $repository) {}

    public function __invoke(CountTagsByCriteriaQuery $query): CountTagsByCriteriaResponse
    {
        $criteria = new Criteria(
            Filters::fromValues($query->filters()),
            Order::fromValues($query->orderBy(), $query->orderType()),
            null,
            null,
        );

        return new CountTagsByCriteriaResponse(
            $this->repository->countByCriteria($criteria),
        );
    }
}
