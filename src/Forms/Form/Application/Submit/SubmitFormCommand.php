<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Application\Submit;

use Dba\DddSkeleton\Shared\Domain\Bus\Command\Command;

final readonly class SubmitFormCommand implements Command
{
    public function __construct(
        private string $key,
        private array $data,
        private ?string $ipAddress,
        private ?string $userAgent,
    ) {}

    public function key(): string { return $this->key; }
    public function data(): array { return $this->data; }
    public function ipAddress(): ?string { return $this->ipAddress; }
    public function userAgent(): ?string { return $this->userAgent; }
}
