<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Application\SearchByCriteria;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;

final readonly class CountCategoriesByCriteriaResponse implements Response
{
    public function __construct(private int $count) {}

    public function count(): int { return $this->count; }

    public function toArray(): array
    {
        return ['count' => $this->count];
    }
}
