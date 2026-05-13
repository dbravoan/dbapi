<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Application\Create;

/**
 * Request-scoped holder used to surface the database-assigned form id back to
 * the create controller after the CommandBus has dispatched the create command.
 *
 * The bus does not return values from command handlers; this is the canonical
 * "out parameter" pattern for that case. Bound as a singleton-per-request in
 * the Laravel container so each HTTP request gets its own clean instance.
 */
final class CreatedFormIdHolder
{
    private ?int $id = null;

    public function set(?int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }
}
