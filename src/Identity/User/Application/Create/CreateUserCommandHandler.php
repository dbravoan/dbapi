<?php

declare(strict_types=1);

namespace Dbapi\Identity\User\Application\Create;

use Dbapi\Identity\User\Domain\User;
use Dbapi\Identity\User\Domain\UserId;
use Dbapi\Identity\User\Domain\UserName;
use Dbapi\Identity\User\Domain\UserRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;

final class CreateUserCommandHandler implements CommandHandler
{
    public function __construct(private readonly UserRepository $repository) {}

    public function __invoke(CreateUserCommand $command): void
    {
        $id = new UserId($command->id());
        $name = new UserName($command->name());
        $model = User::create($id, $name);

        $this->repository->save($model);
    }
}
