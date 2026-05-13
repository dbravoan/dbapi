<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Application\Create;

use Dba\DddSkeleton\Shared\Domain\Bus\Command\Command;

final readonly class CreateLanguageCommand implements Command
{
    public function __construct(
        private string $id,
        private string $code,
        private string $name,
        private string $nativeName,
        private bool $isDefault,
        private bool $isActive,
    ) {}

    public function id(): string         { return $this->id; }
    public function code(): string       { return $this->code; }
    public function name(): string       { return $this->name; }
    public function nativeName(): string { return $this->nativeName; }
    public function isDefault(): bool    { return $this->isDefault; }
    public function isActive(): bool     { return $this->isActive; }
}
