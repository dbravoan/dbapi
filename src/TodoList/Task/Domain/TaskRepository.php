<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Domain;

use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;

interface TaskRepository
{
    public function save(Task $task): void;

    public function remove(TaskId $id): void;

    public function search(TaskId $id): ?Task;

    public function searchByCriteria(Criteria $criteria): array;

    public function countByCriteria(Criteria $criteria): int;
}
