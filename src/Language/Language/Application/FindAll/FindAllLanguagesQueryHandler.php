<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Application\FindAll;

use Dbapi\Language\Language\Application\Response\LanguageResponse;
use Dbapi\Language\Language\Domain\LanguageRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;

final class FindAllLanguagesQueryHandler implements QueryHandler
{
    private LanguageRepository $repository;

    public function __construct(LanguageRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(FindAllLanguagesQuery $query): Response
    {
        $languages = $this->repository->searchAll();

        return new LanguageListResponse(
            array_map(fn ($language) => LanguageResponse::fromAggregate($language), $languages)
        );
    }
}
