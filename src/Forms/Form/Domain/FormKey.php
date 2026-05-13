<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Domain;

use Dba\DddSkeleton\Shared\Domain\ValueObject\StringValueObject;

/** URL-friendly form key (lowercase alphanumeric + hyphens). */
final readonly class FormKey extends StringValueObject
{
    public function __construct(string $value)
    {
        if (!preg_match('/^[a-z0-9-]+$/', $value)) {
            throw new \InvalidArgumentException(
                "Invalid FormKey '{$value}': must contain only lowercase letters, digits and hyphens."
            );
        }

        parent::__construct($value);
    }
}
