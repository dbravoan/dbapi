<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Application\Find;

use Dbapi\Blogging\Post\Application\Response\PostResponse;
use Dbapi\Blogging\Post\Domain\Post;
use Dbapi\Blogging\Post\Domain\PostId;
use Dbapi\Blogging\Post\Domain\PostRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;

final class FindPostQueryHandler implements QueryHandler
{
    public function __construct(private readonly PostRepository $repository) {}

    public function __invoke(FindPostQuery $query): ?PostResponse
    {
        $id = new PostId($query->id());
        $entity = $this->repository->search($id, $query->languageCode());

        return $entity ? PostResponse::fromAggregate($entity) : null;
    }
}
