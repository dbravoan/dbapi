<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Application\Response;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;
 
final readonly class TagsResponse implements Response
{
    /** @var TagResponse[] */
    private array $tags;

    public function __construct(TagResponse ...$tags)
    {
        $this->tags = $tags;
    }

    public function tags(): array
    {
        return $this->tags;
    }

    public function toArray(): array
    {
        return array_map(fn(TagResponse $response) => $response->toArray(), $this->tags);
    }
}
