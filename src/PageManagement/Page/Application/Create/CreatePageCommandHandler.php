<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Application\Create;

use Dbapi\PageManagement\Page\Domain\Page;
use Dbapi\PageManagement\Page\Domain\PageId;
use Dbapi\PageManagement\Page\Domain\PageStatus;
use Dbapi\PageManagement\Page\Domain\PageTranslation;
use Dbapi\PageManagement\Page\Domain\PageRepository;
use Dbapi\Shared\Infrastructure\BlockEditor\BlockEditorContractValidator;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;

final class CreatePageCommandHandler implements CommandHandler
{
    private PageRepository $repository;
    private BlockEditorContractValidator $validator;

    public function __construct(
        PageRepository $repository,
        BlockEditorContractValidator $validator,
    ) {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    public function __invoke(CreatePageCommand $command): void
    {
        $this->validator->assertValid($command->content());

        $translation = new PageTranslation(
            languageCode:   $command->languageCode(),
            slug:           $command->slug(),
            title:          $command->title(),
            content:        $command->content(),
            seoTitle:       $command->seoTitle(),
            seoDescription: $command->seoDescription(),
            seoKeywords:    $command->seoKeywords(),
            canonicalUrl:   $command->canonicalUrl(),
            ogTitle:        $command->ogTitle(),
            ogDescription:  $command->ogDescription(),
            ogImage:        $command->ogImage(),
            structuredData: $command->structuredData(),
        );

        $page = Page::create(
            new PageId($command->id()),
            new PageStatus($command->status()),
            $translation,
        );

        $this->repository->save($page);
    }
}
