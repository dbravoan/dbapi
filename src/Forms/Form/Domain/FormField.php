<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Domain;

final readonly class FormField
{
    public function __construct(
        private string $name,
        private string $label,
        private string $type,
        private bool $required,
    ) {}

    public static function fromArray(array $data): self
    {
        $type = (string) ($data['type'] ?? '');
        $allowedTypes = ['text', 'email', 'tel', 'textarea', 'select', 'checkbox'];

        if (!in_array($type, $allowedTypes, true)) {
            throw new \InvalidArgumentException('Invalid field type: ' . $type);
        }

        return new self(
            (string) ($data['name'] ?? ''),
            (string) ($data['label'] ?? ''),
            $type,
            (bool) ($data['required'] ?? false),
        );
    }

    public function name(): string { return $this->name; }
    public function label(): string { return $this->label; }
    public function type(): string { return $this->type; }
    public function required(): bool { return $this->required; }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
        ];
    }
}
