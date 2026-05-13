<?php

declare(strict_types=1);

namespace Dbapi\Identity\User\Application\Response;

use Dbapi\Identity\User\Domain\User;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;

final readonly class UserResponse implements Response
{
    public function __construct(
        private string $id,
        private string $name,
    ) {}

    public static function fromAggregate(User $user): self
    {
        return new self(
            $user->id()->value(),
            $user->name()->value(),
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
