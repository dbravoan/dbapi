<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Domain;

use Dba\DddSkeleton\Shared\Domain\ValueObject\StringValueObject;

/** Allowed values: draft | published | archived */
final readonly class PageStatus extends StringValueObject
{
    private const ALLOWED = ['draft', 'published', 'archived'];

    public function __construct(string $value)
    {
        if (!in_array($value, self::ALLOWED, true)) {
            throw new \InvalidArgumentException(
                "Invalid PageStatus '{$value}'. Allowed: " . implode(', ', self::ALLOWED)
            );
        }

        parent::__construct($value);
    }

    public static function draft(): self     { return new self('draft'); }
    public static function published(): self { return new self('published'); }
    public static function archived(): self  { return new self('archived'); }
}
