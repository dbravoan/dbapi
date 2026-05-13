<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Domain;

final class FormInactiveException extends \RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf("Form '%s' is not accepting submissions.", $key));
    }
}
