<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Application\Find;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Query;

final readonly class FindFormQuery implements Query
{
    public function __construct(private int $id) {}

    public function id(): int { return $this->id; }
}
