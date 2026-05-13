<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Domain;

use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;

interface PostRepository
{
    public function save(Post $model): void;

    public function remove(PostId $id): void;

    public function search(PostId $id, string $languageCode = 'en'): ?Post;

    public function searchByCriteria(Criteria $criteria, string $languageCode = 'en'): array;

    public function countByCriteria(Criteria $criteria, string $languageCode = 'en'): int;
}
