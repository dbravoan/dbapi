<?php

declare(strict_types=1);

namespace Dbapi\Identity\User\Application\Update;

use Dba\DddSkeleton\Shared\Domain\Bus\Command\Command;

final readonly class UpdateUserCommand implements Command
{
    public function __construct(
        private string $id,
        private string $name,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }
}
