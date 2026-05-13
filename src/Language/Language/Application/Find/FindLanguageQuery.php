<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Application\Find;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Query;

final readonly class FindLanguageQuery implements Query
{
    public function __construct(private string $id) {}

    public function id(): string
    {
        return $this->id;
    }
}
