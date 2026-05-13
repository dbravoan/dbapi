<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Domain;

use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;

interface LanguageRepository
{
    public function save(Language $language): void;

    public function remove(LanguageId $id): void;

    public function search(LanguageId $id): ?Language;

    public function searchByCode(LanguageCode $code): ?Language;

    public function searchAll(): array;

    public function searchByCriteria(Criteria $criteria): array;

    public function countByCriteria(Criteria $criteria): int;
}
