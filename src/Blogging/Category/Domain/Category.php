<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Domain;

use Dba\DddSkeleton\Shared\Domain\Aggregate\AggregateRoot;

final class Category extends AggregateRoot
{
    public function __construct(
        private readonly CategoryId $id,
        private CategoryName $name,
    ) {}

    public static function create(CategoryId $id, CategoryName $name): self
    {
        $model = new self($id, $name);
        $model->record(new CategoryCreatedDomainEvent($id->value(), $name->value()));

        return $model;
    }

    public function id(): CategoryId
    {
        return $this->id;
    }

    public function name(): CategoryName
    {
        return $this->name;
    }

    public function rename(CategoryName $newName): void
    {
        if ($this->name->value() === $newName->value()) {
            return;
        }

        $this->name = $newName;
        $this->record(new CategoryRenamedDomainEvent($this->id->value(), $newName->value()));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            new CategoryId($primitives['id']),
            new CategoryName($primitives['name'])
        );
    }

    public function toPrimitives(): array
    {
        return [
            'id' => $this->id->value(),
            'name' => $this->name->value(),
        ];
    }
}
