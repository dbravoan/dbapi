<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Domain;

use Dba\DddSkeleton\Shared\Domain\ValueObject\StringValueObject;

/** ISO 639-1 language code (e.g. "en", "es", "fr"). Must be 2 lowercase letters. */
final readonly class LanguageCode extends StringValueObject
{
    public function __construct(string $value)
    {
        if (!preg_match('/^[a-z]{2}$/', $value)) {
            throw new \InvalidArgumentException(
                "Invalid LanguageCode '{$value}'. Must be a 2-letter ISO 639-1 code (e.g. 'en', 'es')."
            );
        }

        parent::__construct($value);
    }
}
