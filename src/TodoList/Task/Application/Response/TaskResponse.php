<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Application\Response;

use Dbapi\TodoList\Task\Domain\Task;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;

final class TaskResponse implements Response
{
    private function __construct(
        private readonly string  $id,
        private readonly string  $title,
        private readonly string  $status,
        private readonly string  $priority,
        private readonly ?string $description,
    ) {}

    public static function fromAggregate(Task $task): self
    {
        return new self(
            $task->id()->value(),
            $task->title()->value(),
            $task->status()->value(),
            $task->priority()->value(),
            $task->description(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'description' => $this->description,
        ];
    }
}
