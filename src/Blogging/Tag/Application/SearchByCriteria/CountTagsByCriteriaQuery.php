<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Application\SearchByCriteria;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Query;

final readonly class CountTagsByCriteriaQuery implements Query
{
    public function __construct(
        private array $filters,
        private ?string $orderBy = null,
        private ?string $orderType = null,
        private ?int $limit = null,
        private ?int $offset = null,
    ) {}

    public function filters(): array { return $this->filters; }
    public function orderBy(): ?string { return $this->orderBy; }
    public function orderType(): ?string { return $this->orderType; }
    public function limit(): ?int { return $this->limit; }
    public function offset(): ?int { return $this->offset; }
}
