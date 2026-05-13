<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Application\Create;

use Dbapi\TodoList\Task\Domain\Task;
use Dbapi\TodoList\Task\Domain\TaskId;
use Dbapi\TodoList\Task\Domain\TaskTitle;
use Dbapi\TodoList\Task\Domain\TaskStatus;
use Dbapi\TodoList\Task\Domain\TaskPriority;
use Dbapi\TodoList\Task\Domain\TaskRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;

final class CreateTaskCommandHandler implements CommandHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function __invoke(CreateTaskCommand $command): void
    {
        $task = Task::create(
            new TaskId($command->id()),
            new TaskTitle($command->title()),
            new TaskStatus($command->status()),
            new TaskPriority($command->priority()),
            $command->description(),
        );

        $this->repository->save($task);
    }
}
