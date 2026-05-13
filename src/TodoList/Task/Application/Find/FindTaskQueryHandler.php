<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Application\Find;

use Dbapi\TodoList\Task\Application\Response\TaskResponse;
use Dbapi\TodoList\Task\Domain\TaskId;
use Dbapi\TodoList\Task\Domain\TaskRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;

final class FindTaskQueryHandler implements QueryHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function __invoke(FindTaskQuery $query): ?TaskResponse
    {
        $task = $this->repository->search(new TaskId($query->id()));

        return $task ? TaskResponse::fromAggregate($task) : null;
    }
}
