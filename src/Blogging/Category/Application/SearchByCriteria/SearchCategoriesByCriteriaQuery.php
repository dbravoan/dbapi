<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Application\SearchByCriteria;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Query;

final readonly class SearchCategoriesByCriteriaQuery implements Query
{
    public function __construct(
        private array $filters,
        private ?string $orderBy,
        private ?string $orderType,
        private ?int $limit,
        private ?int $offset,
    ) {}

    public function filters(): array { return $this->filters; }
    public function orderBy(): ?string { return $this->orderBy; }
    public function orderType(): ?string { return $this->orderType; }
    public function limit(): ?int { return $this->limit; }
    public function offset(): ?int { return $this->offset; }
}
