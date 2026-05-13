<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Domain;

final readonly class FormSubmission
{
    public function __construct(
        private int $formId,
        private array $data,
        private ?string $ipAddress,
        private ?string $userAgent,
    ) {}

    public function formId(): int { return $this->formId; }
    public function data(): array { return $this->data; }
    public function ipAddress(): ?string { return $this->ipAddress; }
    public function userAgent(): ?string { return $this->userAgent; }

    public function toPrimitives(): array
    {
        return [
            'form_id' => $this->formId,
            'data' => $this->data,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }
}
