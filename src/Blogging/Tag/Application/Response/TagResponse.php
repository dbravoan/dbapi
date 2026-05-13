<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Application\Response;

use Dbapi\Blogging\Tag\Domain\Tag;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;

final readonly class TagResponse implements Response
{
    public function __construct(
        private string $id,
        private string $name,
        private string $slug,
    ) {}

    public static function fromAggregate(Tag $tag): self
    {
        return new self(
            $tag->id()->value(),
            $tag->name()->value(),
            $tag->slug()->value(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }
}
