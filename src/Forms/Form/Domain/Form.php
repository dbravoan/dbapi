<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Domain;

use Dba\DddSkeleton\Shared\Domain\Aggregate\AggregateRoot;

final class Form extends AggregateRoot
{
    /** @param FormField[] $fields */
    public function __construct(
        private readonly ?FormId $id,
        private readonly FormKey $key,
        private readonly FormName $name,
        private readonly ?FormRecipientEmail $recipientEmail,
        private readonly array $fields,
        private readonly bool $active,
    ) {}

    /** @param FormField[] $fields */
    public static function create(
        FormKey $key,
        FormName $name,
        ?FormRecipientEmail $recipientEmail,
        array $fields,
        bool $active,
    ): self {
        $form = new self(null, $key, $name, $recipientEmail, $fields, $active);
        $form->record(new FormCreatedDomainEvent(
            $key->value(),
            $key->value(),
            $name->value(),
        ));

        return $form;
    }

    public function id(): ?FormId { return $this->id; }
    public function key(): FormKey { return $this->key; }
    public function name(): FormName { return $this->name; }
    public function recipientEmail(): ?FormRecipientEmail { return $this->recipientEmail; }
    /** @return FormField[] */
    public function fields(): array { return $this->fields; }
    public function active(): bool { return $this->active; }

    /** Records a FormSubmittedDomainEvent so it propagates on the next save(). */
    public function recordSubmission(): void
    {
        $this->record(new FormSubmittedDomainEvent(
            $this->key->value(),
            $this->key->value(),
        ));
    }

    public static function fromPrimitives(array $primitives): self
    {
        $fields = array_map(
            static fn (array $field) => FormField::fromArray($field),
            $primitives['fields'] ?? []
        );

        return new self(
            isset($primitives['id']) ? new FormId((int) $primitives['id']) : null,
            new FormKey((string) $primitives['key']),
            new FormName((string) $primitives['name']),
            isset($primitives['recipient_email']) && $primitives['recipient_email'] !== ''
                ? new FormRecipientEmail((string) $primitives['recipient_email'])
                : null,
            $fields,
            (bool) ($primitives['active'] ?? true),
        );
    }

    public function toPrimitives(): array
    {
        return [
            'id' => $this->id?->value(),
            'key' => $this->key->value(),
            'name' => $this->name->value(),
            'recipient_email' => $this->recipientEmail?->value(),
            'fields' => array_map(static fn (FormField $field) => $field->toArray(), $this->fields),
            'active' => $this->active,
        ];
    }
}
