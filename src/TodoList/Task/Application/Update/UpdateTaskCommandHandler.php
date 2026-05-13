<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Application\Update;

use Dbapi\TodoList\Task\Domain\Task;
use Dbapi\TodoList\Task\Domain\TaskId;
use Dbapi\TodoList\Task\Domain\TaskPriority;
use Dbapi\TodoList\Task\Domain\TaskRepository;
use Dbapi\TodoList\Task\Domain\TaskStatus;
use Dbapi\TodoList\Task\Domain\TaskTitle;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;

final class UpdateTaskCommandHandler implements CommandHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function __invoke(UpdateTaskCommand $command): void
    {
        $id       = new TaskId($command->id());
        $title    = new TaskTitle($command->title());
        $status   = new TaskStatus($command->status());
        $priority = new TaskPriority($command->priority());

        $task = $this->repository->search($id);

        // Upsert: PUT may target a non-existent id, in which case we create.
        // For existing aggregates we mutate in place so the *Updated* event is
        // recorded, NOT the *Created* event (fixes audit finding #15).
        if ($task === null) {
            $task = Task::create($id, $title, $status, $priority, $command->description());
        } else {
            $task->update($title, $status, $priority, $command->description());
        }

        $this->repository->save($task);
    }
}
