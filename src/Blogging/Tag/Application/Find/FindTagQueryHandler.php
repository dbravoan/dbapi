<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Application\Find;

use Dbapi\Blogging\Tag\Application\Response\TagResponse;
use Dbapi\Blogging\Tag\Domain\Tag;
use Dbapi\Blogging\Tag\Domain\TagId;
use Dbapi\Blogging\Tag\Domain\TagRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;

final class FindTagQueryHandler implements QueryHandler
{
    public function __construct(private readonly TagRepository $repository) {}

    public function __invoke(FindTagQuery $query): ?TagResponse
    {
        $id = new TagId($query->id());
        $entity = $this->repository->search($id);

        return $entity ? TagResponse::fromAggregate($entity) : null;
    }
}
