<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Domain;

use Dba\DddSkeleton\Shared\Domain\ValueObject\StringValueObject;

/** Allowed values: pending | in_progress | done */
final readonly class TaskStatus extends StringValueObject
{
    private const ALLOWED = ['pending', 'in_progress', 'done'];

    public function __construct(string $value)
    {
        if (!in_array($value, self::ALLOWED, true)) {
            throw new \InvalidArgumentException(
                "Invalid TaskStatus '{$value}'. Allowed: " . implode(', ', self::ALLOWED)
            );
        }

        parent::__construct($value);
    }

    public static function pending(): self    { return new self('pending'); }
    public static function inProgress(): self { return new self('in_progress'); }
    public static function done(): self       { return new self('done'); }
}
