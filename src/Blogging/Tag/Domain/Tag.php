<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Domain;

use Dba\DddSkeleton\Shared\Domain\Aggregate\AggregateRoot;

final class Tag extends AggregateRoot
{
    public function __construct(
        private readonly TagId $id,
        private TagName $name,
        private readonly TagSlug $slug,
    ) {}

    public static function create(TagId $id, TagName $name, TagSlug $slug): self
    {
        $tag = new self($id, $name, $slug);
        $tag->record(new TagCreatedDomainEvent($id->value(), $name->value()));

        return $tag;
    }

    public function id(): TagId { return $this->id; }
    public function name(): TagName { return $this->name; }
    public function slug(): TagSlug { return $this->slug; }

    public function rename(TagName $newName): void
    {
        if ($this->name->value() === $newName->value()) {
            return;
        }

        $this->name = $newName;
        $this->record(new TagRenamedDomainEvent($this->id->value(), $newName->value()));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            new TagId($primitives['id']),
            new TagName($primitives['name']),
            new TagSlug($primitives['slug'])
        );
    }

    public function toPrimitives(): array
    {
        return [
            'id' => $this->id->value(),
            'name' => $this->name->value(),
            'slug' => $this->slug->value(),
        ];
    }
}
