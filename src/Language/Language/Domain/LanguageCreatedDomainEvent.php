<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Domain;

use Dba\DddSkeleton\Shared\Domain\Bus\Event\DomainEvent;

readonly final class LanguageCreatedDomainEvent extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private string $code,
        private string $name,
        ?string $eventId    = null,
        ?string $occurredOn = null,
    ) {
        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function eventName(): string { return 'language.language.created'; }

    public function code(): string { return $this->code; }
    public function name(): string { return $this->name; }

    public function toPrimitives(): array
    {
        return ['code' => $this->code, 'name' => $this->name];
    }

    public static function fromPrimitives(
        string $aggregateId,
        array  $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self($aggregateId, $body['code'], $body['name'], $eventId, $occurredOn);
    }
}
