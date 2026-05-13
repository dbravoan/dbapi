<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Application\Find;

use Dbapi\Language\Language\Application\Response\LanguageResponse;
use Dbapi\Language\Language\Domain\LanguageId;
use Dbapi\Language\Language\Domain\LanguageRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;

final class FindLanguageQueryHandler implements QueryHandler
{
    private LanguageRepository $repository;

    public function __construct(LanguageRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(FindLanguageQuery $query): ?LanguageResponse
    {
        $language = $this->repository->search(new LanguageId($query->id()));

        return $language ? LanguageResponse::fromAggregate($language) : null;
    }
}
