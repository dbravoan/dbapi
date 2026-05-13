<?php

declare(strict_types=1);

namespace Dbapi\Identity\User\Application\Find;

use Dbapi\Identity\User\Application\Response\UserResponse;
use Dbapi\Identity\User\Domain\User;
use Dbapi\Identity\User\Domain\UserId;
use Dbapi\Identity\User\Domain\UserRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;

final class FindUserQueryHandler implements QueryHandler
{
    public function __construct(private readonly UserRepository $repository) {}

    public function __invoke(FindUserQuery $query): ?UserResponse
    {
        $id = new UserId($query->id());
        $entity = $this->repository->search($id);

        return $entity ? UserResponse::fromAggregate($entity) : null;
    }
}
