<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Application\Find;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Query;

final readonly class FindPostQuery implements Query
{
    public function __construct(private string $id, private string $languageCode = 'en') {}

    public function id(): string
    {
        return $this->id;
    }

    public function languageCode(): string
    {
        return $this->languageCode;
    }
}
