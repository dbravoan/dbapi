<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Application\Update;

use Dba\DddSkeleton\Shared\Domain\Bus\Command\Command;

final readonly class UpdateCategoryCommand implements Command
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
