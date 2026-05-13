<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Domain;

use Dba\DddSkeleton\Shared\Domain\Bus\Event\DomainEvent;

final readonly class PageCreatedDomainEvent extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private string $languageCode,
        private string $slug,
        private string $title,
        ?string $eventId    = null,
        ?string $occurredOn = null,
    ) {
        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function eventName(): string { return 'pagemanagement.page.created'; }

    public function languageCode(): string { return $this->languageCode; }
    public function slug(): string         { return $this->slug; }
    public function title(): string        { return $this->title; }

    public function toPrimitives(): array
    {
        return [
            'language_code' => $this->languageCode,
            'slug'          => $this->slug,
            'title'         => $this->title,
        ];
    }

    public static function fromPrimitives(
        string $aggregateId,
        array  $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self(
            $aggregateId,
            $body['language_code'],
            $body['slug'],
            $body['title'],
            $eventId,
            $occurredOn,
        );
    }
}
