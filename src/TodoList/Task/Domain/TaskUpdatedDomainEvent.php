<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Domain;

use Dba\DddSkeleton\Shared\Domain\Bus\Event\DomainEvent;

final readonly class TaskUpdatedDomainEvent extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private string $title,
        ?string $eventId = null,
        ?string $occurredOn = null,
    ) {
        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self(
            $aggregateId,
            $body['title'] ?? '',
            $eventId,
            $occurredOn,
        );
    }

    public static function eventName(): string
    {
        return 'todolist.task.updated';
    }

    public function toPrimitives(): array
    {
        return [
            'title' => $this->title,
        ];
    }

    public function title(): string
    {
        return $this->title;
    }
}
