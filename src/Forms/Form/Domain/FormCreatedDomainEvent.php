<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Domain;

use Dba\DddSkeleton\Shared\Domain\Bus\Event\DomainEvent;

final readonly class FormCreatedDomainEvent extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private string $key,
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
            $body['key'] ?? '',
            $body['name'] ?? '',
            $eventId,
            $occurredOn,
        );
    }

    public static function eventName(): string
    {
        return 'forms.form.created';
    }

    public function toPrimitives(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
        ];
    }

    public function key(): string { return $this->key; }
    public function name(): string { return $this->name; }
}
