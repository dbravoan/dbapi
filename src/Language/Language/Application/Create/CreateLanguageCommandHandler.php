<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Application\Create;

use Dbapi\Language\Language\Domain\Language;
use Dbapi\Language\Language\Domain\LanguageId;
use Dbapi\Language\Language\Domain\LanguageCode;
use Dbapi\Language\Language\Domain\LanguageName;
use Dbapi\Language\Language\Domain\LanguageNativeName;
use Dbapi\Language\Language\Domain\LanguageIsDefault;
use Dbapi\Language\Language\Domain\LanguageIsActive;
use Dbapi\Language\Language\Domain\LanguageRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;

final class CreateLanguageCommandHandler implements CommandHandler
{
    private LanguageRepository $repository;

    public function __construct(LanguageRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(CreateLanguageCommand $command): void
    {
        $language = Language::create(
            new LanguageId($command->id()),
            new LanguageCode($command->code()),
            new LanguageName($command->name()),
            new LanguageNativeName($command->nativeName()),
            new LanguageIsDefault($command->isDefault()),
            new LanguageIsActive($command->isActive()),
        );

        $this->repository->save($language);
    }
}
