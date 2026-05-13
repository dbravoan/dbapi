<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Application\Create;

use Dba\DddSkeleton\Shared\Domain\Bus\Command\Command;

final readonly class CreateTagCommand implements Command
{
    public function __construct(
        private string $id,
        private string $name,
        private string $slug,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }
}
