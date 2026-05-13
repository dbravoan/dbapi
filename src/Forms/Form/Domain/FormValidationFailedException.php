<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Domain;

final class FormValidationFailedException extends \RuntimeException
{
    /** @var array<string, array<int, string>> */
    private array $errors;

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
