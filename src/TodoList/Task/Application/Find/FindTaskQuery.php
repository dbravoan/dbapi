<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Application\Find;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Query;

final class FindTaskQuery implements Query
{
    public function __construct(private readonly string $id) {}

    public function id(): string { return $this->id; }
}
