<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Domain;

use Dba\DddSkeleton\Shared\Domain\ValueObject\StringValueObject;

final readonly class TaskId extends StringValueObject {}
