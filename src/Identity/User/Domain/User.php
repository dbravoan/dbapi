<?php

declare(strict_types=1);

namespace Dbapi\Identity\User\Domain;

use Dba\DddSkeleton\Shared\Domain\Aggregate\AggregateRoot;

final class User extends AggregateRoot
{
    public function __construct(
        private readonly UserId $id,
        private UserName $name,
    ) {}

    public static function create(UserId $id, UserName $name): self
    {
        $model = new self($id, $name);
        $model->record(new UserCreatedDomainEvent($id->value(), $name->value()));

        return $model;
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function rename(UserName $newName): void
    {
        if ($this->name->value() === $newName->value()) {
            return;
        }

        $this->name = $newName;
        $this->record(new UserRenamedDomainEvent($this->id->value(), $newName->value()));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            new UserId($primitives['id']),
            new UserName($primitives['name'])
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
