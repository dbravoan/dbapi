<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Domain;

use Dba\DddSkeleton\Shared\Domain\Aggregate\AggregateRoot;

final class Language extends AggregateRoot
{
    private LanguageId $id;
    private LanguageCode $code;
    private LanguageName $name;
    private LanguageNativeName $nativeName;
    private LanguageIsDefault $isDefault;
    private LanguageIsActive $isActive;

    public function __construct(
        LanguageId         $id,
        LanguageCode       $code,
        LanguageName       $name,
        LanguageNativeName $nativeName,
        LanguageIsDefault  $isDefault,
        LanguageIsActive   $isActive,
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->nativeName = $nativeName;
        $this->isDefault = $isDefault;
        $this->isActive = $isActive;
    }

    public static function create(
        LanguageId         $id,
        LanguageCode       $code,
        LanguageName       $name,
        LanguageNativeName $nativeName,
        LanguageIsDefault  $isDefault,
        LanguageIsActive   $isActive,
    ): self {
        $language = new self($id, $code, $name, $nativeName, $isDefault, $isActive);
        $language->record(new LanguageCreatedDomainEvent($id->value(), $code->value(), $name->value()));

        return $language;
    }

    public function id(): LanguageId                 { return $this->id; }
    public function code(): LanguageCode             { return $this->code; }
    public function name(): LanguageName             { return $this->name; }
    public function nativeName(): LanguageNativeName { return $this->nativeName; }
    public function isDefault(): LanguageIsDefault   { return $this->isDefault; }
    public function isActive(): LanguageIsActive     { return $this->isActive; }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            new LanguageId($primitives['id']),
            new LanguageCode($primitives['code']),
            new LanguageName($primitives['name']),
            new LanguageNativeName($primitives['native_name']),
            new LanguageIsDefault((bool) $primitives['is_default']),
            new LanguageIsActive((bool) $primitives['is_active']),
        );
    }

    public function toPrimitives(): array
    {
        return [
            'id'          => $this->id->value(),
            'code'        => $this->code->value(),
            'name'        => $this->name->value(),
            'native_name' => $this->nativeName->value(),
            'is_default'  => $this->isDefault->value(),
            'is_active'   => $this->isActive->value(),
        ];
    }
}
