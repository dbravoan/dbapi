<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Domain;

use Dba\DddSkeleton\Shared\Domain\Bus\Event\DomainEvent;

final readonly class TaskCreatedDomainEvent extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private string $title,
        ?string $eventId = null,
        ?string $occurredOn = null,
    ) {
        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function eventName(): string { return 'todolist.task.created'; }

    public function title(): string { return $this->title; }

    public function toPrimitives(): array
    {
        return ['title' => $this->title];
    }

    public static function fromPrimitives(
        string $aggregateId,
        array  $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self($aggregateId, $body['title'], $eventId, $occurredOn);
    }
}
