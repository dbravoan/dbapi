<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Infrastructure\SpamProtection;

final class TooManySubmissionsException extends \RuntimeException
{
    public static function forFormAndIp(): self
    {
        return new self('Too many submissions. Try again later.');
    }
}
