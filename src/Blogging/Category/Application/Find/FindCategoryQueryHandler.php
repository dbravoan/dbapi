<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Application\Find;

use Dbapi\Blogging\Category\Application\Response\CategoryResponse;
use Dbapi\Blogging\Category\Domain\Category;
use Dbapi\Blogging\Category\Domain\CategoryId;
use Dbapi\Blogging\Category\Domain\CategoryRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;

final class FindCategoryQueryHandler implements QueryHandler
{
    public function __construct(private readonly CategoryRepository $repository) {}

    public function __invoke(FindCategoryQuery $query): ?CategoryResponse
    {
        $id = new CategoryId($query->id());
        $entity = $this->repository->search($id);

        return $entity ? CategoryResponse::fromAggregate($entity) : null;
    }
}
