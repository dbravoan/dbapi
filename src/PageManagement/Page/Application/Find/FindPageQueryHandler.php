<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Application\Find;

use Dbapi\PageManagement\Page\Application\Response\PageResponse;
use Dbapi\PageManagement\Page\Domain\PageId;
use Dbapi\PageManagement\Page\Domain\PageRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;

final class FindPageQueryHandler implements QueryHandler
{
    public function __construct(private readonly PageRepository $repository) {}

    public function __invoke(FindPageQuery $query): ?PageResponse
    {
        $page = $this->repository->search(
            new PageId($query->id()),
            $query->languageCode()
        );

        return $page ? PageResponse::fromAggregate($page) : null;
    }
}
