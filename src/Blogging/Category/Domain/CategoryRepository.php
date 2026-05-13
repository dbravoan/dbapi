<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Domain;

use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;

interface CategoryRepository
{
    public function save(Category $model): void;

    public function remove(CategoryId $id): void;

    public function search(CategoryId $id): ?Category;

    public function searchByCriteria(Criteria $criteria): array;

    public function countByCriteria(Criteria $criteria): int;
}
