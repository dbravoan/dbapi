<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Application\SearchByCriteria;

use Dbapi\Blogging\Category\Application\Response\CategoriesResponse;
use Dbapi\Blogging\Category\Application\Response\CategoryResponse;
use Dbapi\Blogging\Category\Domain\Category;
use Dbapi\Blogging\Category\Domain\CategoryRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Domain\Criteria\Filters;
use Dba\DddSkeleton\Shared\Domain\Criteria\Order;

final class SearchCategoriesByCriteriaQueryHandler implements QueryHandler
{
    public function __construct(private readonly CategoryRepository $repository) {}

    public function __invoke(SearchCategoriesByCriteriaQuery $query): CategoriesResponse
    {
        $criteria = new Criteria(
            Filters::fromValues($query->filters()),
            Order::fromValues($query->orderBy(), $query->orderType()),
            $query->offset(),
            $query->limit(),
        );

        $entities = $this->repository->searchByCriteria($criteria);

        $responses = array_map(
            static fn (Category $category) => CategoryResponse::fromAggregate($category),
            $entities,
        );

        return new CategoriesResponse(...$responses);
    }
}
