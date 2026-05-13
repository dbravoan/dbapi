<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Domain;

use Dba\DddSkeleton\Shared\Domain\Aggregate\AggregateRoot;

final class Task extends AggregateRoot
{
    public function __construct(
        private readonly TaskId $id,
        private TaskTitle       $title,
        private TaskStatus      $status,
        private TaskPriority    $priority,
        private ?string         $description,
    ) {}

    public static function create(
        TaskId       $id,
        TaskTitle    $title,
        TaskStatus   $status,
        TaskPriority $priority,
        ?string      $description,
    ): self {
        $task = new self($id, $title, $status, $priority, $description);
        $task->record(new TaskCreatedDomainEvent($id->value(), $title->value()));

        return $task;
    }

    public function id(): TaskId          { return $this->id; }
    public function title(): TaskTitle    { return $this->title; }
    public function status(): TaskStatus  { return $this->status; }
    public function priority(): TaskPriority { return $this->priority; }
    public function description(): ?string { return $this->description; }

    /**
     * Apply an in-place update of the mutable fields and record a TaskUpdatedDomainEvent.
     * Used by the Update use case to replace state without re-emitting the Created event.
     */
    public function update(
        TaskTitle    $title,
        TaskStatus   $status,
        TaskPriority $priority,
        ?string      $description,
    ): void {
        $this->title       = $title;
        $this->status      = $status;
        $this->priority    = $priority;
        $this->description = $description;

        $this->record(new TaskUpdatedDomainEvent($this->id->value(), $title->value()));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            new TaskId($primitives['id']),
            new TaskTitle($primitives['title']),
            new TaskStatus($primitives['status']),
            new TaskPriority($primitives['priority']),
            $primitives['description'] ?? null,
        );
    }

    public function toPrimitives(): array
    {
        return [
            'id'          => $this->id->value(),
            'title'       => $this->title->value(),
            'status'      => $this->status->value(),
            'priority'    => $this->priority->value(),
            'description' => $this->description,
        ];
    }
}
