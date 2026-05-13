<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Domain;

use Dba\DddSkeleton\Shared\Domain\Bus\Event\DomainEvent;

final readonly class PostCreatedDomainEvent extends DomainEvent
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
        // Accept either the current `title` key or the legacy `name` key
        // (the property used to be called $name; older event-stream rows
        // serialised it as 'name'). Fall back to empty string rather than
        // raising a non-domain ErrorException on a malformed payload.
        $title = $body['title'] ?? $body['name'] ?? '';

        return new self(
            $aggregateId,
            (string) $title,
            $eventId,
            $occurredOn,
        );
    }

    public static function eventName(): string
    {
        return 'post.created';
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
