<?php

declare(strict_types=1);

namespace Dbapi\Identity\User\Domain;

use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;

interface UserRepository
{
    public function save(User $model): void;

    public function remove(UserId $id): void;

    public function search(UserId $id): ?User;

    public function searchByCriteria(Criteria $criteria): array;

    public function countByCriteria(Criteria $criteria): int;
}
