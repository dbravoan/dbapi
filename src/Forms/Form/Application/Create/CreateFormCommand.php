<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Application\Create;

use Dba\DddSkeleton\Shared\Domain\Bus\Command\Command;

final readonly class CreateFormCommand implements Command
{
    public function __construct(
        private string $name,
        private string $key,
        private ?string $recipientEmail,
        private bool $active,
        private array $fields,
    ) {}

    public function name(): string { return $this->name; }
    public function key(): string { return $this->key; }
    public function recipientEmail(): ?string { return $this->recipientEmail; }
    public function active(): bool { return $this->active; }
    public function fields(): array { return $this->fields; }
}
