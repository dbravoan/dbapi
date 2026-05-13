<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Application\Create;

use Dba\DddSkeleton\Shared\Domain\Bus\Command\Command;

final class CreateTaskCommand implements Command
{
    public function __construct(
        private readonly string  $id,
        private readonly string  $title,
        private readonly string  $status,
        private readonly string  $priority,
        private readonly ?string $description,
    ) {}

    public function id(): string          { return $this->id; }
    public function title(): string       { return $this->title; }
    public function status(): string      { return $this->status; }
    public function priority(): string    { return $this->priority; }
    public function description(): ?string { return $this->description; }
}
