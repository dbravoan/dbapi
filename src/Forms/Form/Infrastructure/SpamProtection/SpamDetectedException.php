<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Infrastructure\SpamProtection;

final class SpamDetectedException extends \RuntimeException
{
    public static function honeypotFilled(): self
    {
        return new self('Spam detected by honeypot.');
    }
}
