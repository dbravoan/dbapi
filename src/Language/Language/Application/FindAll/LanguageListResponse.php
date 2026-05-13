<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Application\FindAll;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;

final readonly class LanguageListResponse implements Response
{
    /** @param array<int, object> $items */
    public function __construct(private array $items) {}

    /** @return array<int, object> */
    public function items(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return array_map(static fn ($item) => $item->toArray(), $this->items);
    }
}
