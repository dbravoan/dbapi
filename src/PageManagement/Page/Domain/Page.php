<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Domain;

use Dba\DddSkeleton\Shared\Domain\Aggregate\AggregateRoot;

final class Page extends AggregateRoot
{
    public function __construct(
        private readonly PageId          $id,
        private readonly PageStatus      $status,
        private readonly PageTranslation $translation,
    ) {}

    public static function create(
        PageId          $id,
        PageStatus      $status,
        PageTranslation $translation,
    ): self {
        $page = new self($id, $status, $translation);
        $page->record(new PageCreatedDomainEvent(
            $id->value(),
            $translation->languageCode,
            $translation->slug,
            $translation->title,
        ));

        return $page;
    }

    public function id(): PageId                   { return $this->id; }
    public function status(): PageStatus           { return $this->status; }
    public function translation(): PageTranslation { return $this->translation; }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            new PageId($primitives['id']),
            new PageStatus($primitives['status']),
            PageTranslation::fromPrimitives($primitives),
        );
    }

    /** Returns only the non-translatable columns (main pages table). */
    public function toPrimitives(): array
    {
        return [
            'id'     => $this->id->value(),
            'status' => $this->status->value(),
        ];
    }
}
