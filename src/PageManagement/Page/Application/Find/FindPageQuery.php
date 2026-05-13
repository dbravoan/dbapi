<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Application\Find;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Query;

final class FindPageQuery implements Query
{
    public function __construct(
        private readonly string $id,
        private readonly string $languageCode,
    ) {}

    public function id(): string           { return $this->id; }
    public function languageCode(): string { return $this->languageCode; }
}
