<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Application\Find;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Query;

final readonly class FindTagQuery implements Query
{
    public function __construct(private string $id) {}

    public function id(): string
    {
        return $this->id;
    }
}
