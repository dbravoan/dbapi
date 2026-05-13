<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Domain;

use Dba\DddSkeleton\Shared\Domain\ValueObject\StringValueObject;

/** Optional contact email that receives form submissions. */
final readonly class FormRecipientEmail extends StringValueObject
{
    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid FormRecipientEmail '{$value}'.");
        }

        parent::__construct($value);
    }
}
