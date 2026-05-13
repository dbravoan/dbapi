<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Domain;

use Dba\DddSkeleton\Shared\Domain\ValueObject\StringValueObject;

final readonly class PostCategory extends StringValueObject {}
