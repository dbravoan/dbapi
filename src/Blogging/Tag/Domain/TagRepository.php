<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Domain;

use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;

interface TagRepository
{
    public function save(Tag $model): void;

    public function remove(TagId $id): void;

    public function search(TagId $id): ?Tag;

    public function searchByName(TagName $name): ?Tag;

    public function searchByCriteria(Criteria $criteria): array;

    public function countByCriteria(Criteria $criteria): int;
}
