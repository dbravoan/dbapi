<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Domain;

use Dba\DddSkeleton\Shared\Domain\Bus\Event\DomainEvent;

final readonly class TagRenamedDomainEvent extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private string $name,
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
            $body['name'] ?? '',
            $eventId,
            $occurredOn,
        );
    }

    public static function eventName(): string
    {
        return 'tag.renamed';
    }

    public function toPrimitives(): array
    {
        return [
            'name' => $this->name,
        ];
    }

    public function name(): string
    {
        return $this->name;
    }
}
