<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Application\Find;

use Dbapi\Forms\Form\Application\Response\FormResponse;
use Dbapi\Forms\Form\Domain\FormId;
use Dbapi\Forms\Form\Domain\FormRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryHandler;

final class FindFormQueryHandler implements QueryHandler
{
    public function __construct(private readonly FormRepository $repository) {}

    public function __invoke(FindFormQuery $query): ?FormResponse
    {
        $form = $this->repository->search(new FormId($query->id()));

        return $form ? FormResponse::fromAggregate($form) : null;
    }
}
