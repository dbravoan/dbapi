<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Application\Response;

use Dbapi\Forms\Form\Domain\Form;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;

final readonly class FormResponse implements Response
{
    public function __construct(
        private int $id,
        private string $name,
        private string $key,
        private ?string $recipientEmail,
        private bool $active,
        private array $fields,
    ) {}

    public static function fromAggregate(Form $form): self
    {
        return new self(
            $form->id()?->value() ?? 0,
            $form->name()->value(),
            $form->key()->value(),
            $form->recipientEmail()?->value(),
            $form->active(),
            array_map(static fn ($field) => $field->toArray(), $form->fields()),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'key' => $this->key,
            'recipient_email' => $this->recipientEmail,
            'active' => $this->active,
            'fields' => $this->fields,
        ];
    }
}
