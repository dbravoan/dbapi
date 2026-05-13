<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Application\Response;

use Dbapi\Language\Language\Domain\Language;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;

final readonly class LanguageResponse implements Response
{
    public function __construct(
        private string $id,
        private string $code,
        private string $name,
        private string $nativeName,
        private bool $isDefault,
        private bool $isActive,
    ) {}

    public static function fromAggregate(Language $language): self
    {
        return new self(
            $language->id()->value(),
            $language->code()->value(),
            $language->name()->value(),
            $language->nativeName()->value(),
            $language->isDefault()->value(),
            $language->isActive()->value(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'name'        => $this->name,
            'native_name' => $this->nativeName,
            'is_default'  => $this->isDefault,
            'is_active'   => $this->isActive,
        ];
    }
}
