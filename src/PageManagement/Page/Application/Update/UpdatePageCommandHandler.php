<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Application\Update;

use Dbapi\PageManagement\Page\Domain\Page;
use Dbapi\PageManagement\Page\Domain\PageId;
use Dbapi\PageManagement\Page\Domain\PageStatus;
use Dbapi\PageManagement\Page\Domain\PageTranslation;
use Dbapi\PageManagement\Page\Domain\PageRepository;
use Dbapi\Shared\Infrastructure\BlockEditor\BlockEditorContractValidator;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;
use RuntimeException;

final class UpdatePageCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly PageRepository               $repository,
        private readonly BlockEditorContractValidator $validator,
    ) {}

    public function __invoke(UpdatePageCommand $command): void
    {
        $existing = $this->repository->search(
            new PageId($command->id()),
            $command->languageCode()
        );

        if (null === $existing) {
            throw new RuntimeException("Page '{$command->id()}' not found.");
        }

        $existingTranslation = $existing->translation();

        if ($command->content() !== null) {
            $this->validator->assertValid($command->content());
        }

        $translation = new PageTranslation(
            languageCode:   $command->languageCode(),
            slug:           $command->slug()           ?? $existingTranslation->slug,
            title:          $command->title()          ?? $existingTranslation->title,
            content:        $command->content()        ?? $existingTranslation->content,
            seoTitle:       $command->seoTitle()       ?? $existingTranslation->seoTitle,
            seoDescription: $command->seoDescription() ?? $existingTranslation->seoDescription,
            seoKeywords:    $command->seoKeywords()    ?? $existingTranslation->seoKeywords,
            canonicalUrl:   $command->canonicalUrl()   ?? $existingTranslation->canonicalUrl,
            ogTitle:        $command->ogTitle()        ?? $existingTranslation->ogTitle,
            ogDescription:  $command->ogDescription()  ?? $existingTranslation->ogDescription,
            ogImage:        $command->ogImage()        ?? $existingTranslation->ogImage,
            structuredData: $command->structuredData() ?? $existingTranslation->structuredData,
        );

        $status = $command->status() !== null
            ? new PageStatus($command->status())
            : $existing->status();

        $updated = new Page($existing->id(), $status, $translation);

        $this->repository->save($updated);
    }
}
