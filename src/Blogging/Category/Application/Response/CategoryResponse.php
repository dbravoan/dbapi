<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Application\Response;

use Dbapi\Blogging\Category\Domain\Category;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;

final readonly class CategoryResponse implements Response
{
    public function __construct(
        private string $id,
        private string $name,
    ) {}

    public static function fromAggregate(Category $category): self
    {
        return new self(
            $category->id()->value(),
            $category->name()->value(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
        ];
    }
}
