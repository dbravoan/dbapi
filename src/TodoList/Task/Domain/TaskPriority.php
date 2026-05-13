<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Domain;

use Dba\DddSkeleton\Shared\Domain\ValueObject\StringValueObject;

/** Allowed values: low | medium | high */
final readonly class TaskPriority extends StringValueObject
{
    private const ALLOWED = ['low', 'medium', 'high'];

    public function __construct(string $value)
    {
        if (!in_array($value, self::ALLOWED, true)) {
            throw new \InvalidArgumentException(
                "Invalid TaskPriority '{$value}'. Allowed: " . implode(', ', self::ALLOWED)
            );
        }

        parent::__construct($value);
    }

    public static function low(): self    { return new self('low'); }
    public static function medium(): self { return new self('medium'); }
    public static function high(): self   { return new self('high'); }
}
