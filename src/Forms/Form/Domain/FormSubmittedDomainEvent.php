<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Domain;

use Dba\DddSkeleton\Shared\Domain\Bus\Event\DomainEvent;

final readonly class FormSubmittedDomainEvent extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private string $key,
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
            $eventId,
            $occurredOn,
        );
    }

    public static function eventName(): string
    {
        return 'forms.form.submitted';
    }

    public function toPrimitives(): array
    {
        return [
            'key' => $this->key,
        ];
    }

    public function key(): string { return $this->key; }
}
