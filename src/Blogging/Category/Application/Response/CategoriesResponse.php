<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Application\Response;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;

final readonly class CategoriesResponse implements Response
{
    /** @var CategoryResponse[] */
    private array $categories;

    public function __construct(CategoryResponse ...$categories)
    {
        $this->categories = $categories;
    }

    /** @return CategoryResponse[] */
    public function categories(): array
    {
        return $this->categories;
    }

    public function toArray(): array
    {
        return array_map(
            static fn (CategoryResponse $response) => $response->toArray(),
            $this->categories,
        );
    }
}
