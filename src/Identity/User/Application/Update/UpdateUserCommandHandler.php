<?php

declare(strict_types=1);

namespace Dbapi\Identity\User\Application\Update;

use Dbapi\Identity\User\Domain\UserId;
use Dbapi\Identity\User\Domain\UserName;
use Dbapi\Identity\User\Domain\UserRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;

final class UpdateUserCommandHandler implements CommandHandler
{
    public function __construct(private readonly UserRepository $repository) {}

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $this->repository->search(new UserId($command->id()));

        if (null === $user) {
            return;
        }

        $user->rename(new UserName($command->name()));

        $this->repository->save($user);
    }
}
