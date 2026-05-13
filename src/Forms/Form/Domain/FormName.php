<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Domain;

use Dba\DddSkeleton\Shared\Domain\ValueObject\StringValueObject;

final readonly class FormName extends StringValueObject
{
    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('FormName cannot be empty.');
        }
        if (strlen($trimmed) > 255) {
            throw new \InvalidArgumentException('FormName cannot exceed 255 characters.');
        }

        parent::__construct($trimmed);
    }
}
