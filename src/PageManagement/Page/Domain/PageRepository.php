<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Domain;

use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;

interface PageRepository
{
    public function save(Page $page): void;

    public function remove(PageId $id): void;

    /** Find page with its translation for the given language code. */
    public function search(PageId $id, string $languageCode): ?Page;

    public function searchByCriteria(Criteria $criteria, string $languageCode): array;

    public function countByCriteria(Criteria $criteria, string $languageCode = 'en'): int;
}
