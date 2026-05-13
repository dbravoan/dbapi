<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Domain;

use Dba\DddSkeleton\Shared\Domain\ValueObject\IntValueObject;

/** Surrogate id assigned by the database. May be null on aggregates not yet persisted. */
final readonly class FormId extends IntValueObject
{
    public function __construct(int $value)
    {
        if ($value < 1) {
            throw new \InvalidArgumentException("Invalid FormId '{$value}': must be a positive integer.");
        }

        parent::__construct($value);
    }
}
