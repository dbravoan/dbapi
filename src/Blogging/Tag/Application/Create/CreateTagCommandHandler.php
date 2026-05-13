<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Application\Create;

use Dbapi\Blogging\Tag\Domain\Tag;
use Dbapi\Blogging\Tag\Domain\TagId;
use Dbapi\Blogging\Tag\Domain\TagName;
use Dbapi\Blogging\Tag\Domain\TagRepository;
use Dbapi\Blogging\Tag\Domain\TagSlug;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;

final class CreateTagCommandHandler implements CommandHandler
{
    public function __construct(private readonly TagRepository $repository) {}

    public function __invoke(CreateTagCommand $command): void
    {
        $tag = Tag::create(
            new TagId($command->id()),
            new TagName($command->name()),
            new TagSlug($command->slug()),
        );

        $this->repository->save($tag);
    }
}
