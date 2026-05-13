<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Application\Response;

use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;
 
final readonly class PostsResponse implements Response
{
    /** @var PostResponse[] */
    private array $posts;

    public function __construct(PostResponse ...$posts)
    {
        $this->posts = $posts;
    }

    public function posts(): array
    {
        return $this->posts;
    }

    public function toArray(): array
    {
        return array_map(fn(PostResponse $response) => $response->toArray(), $this->posts);
    }
}
