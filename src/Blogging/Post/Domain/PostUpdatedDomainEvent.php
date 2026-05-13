<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Domain;

use Dba\DddSkeleton\Shared\Domain\Bus\Event\DomainEvent;

final readonly class PostUpdatedDomainEvent extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private string $title,
        private string $language,
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
            $body['language'] ?? '',
            $eventId,
            $occurredOn,
        );
    }

    public static function eventName(): string
    {
        return 'post.updated';
    }

    public function toPrimitives(): array
    {
        return [
            'title' => $this->title,
            'language' => $this->language,
        ];
    }

    public function title(): string
    {
        return $this->title;
    }

    public function language(): string
    {
        return $this->language;
    }
}
