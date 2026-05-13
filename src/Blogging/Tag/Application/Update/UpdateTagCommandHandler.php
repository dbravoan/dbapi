<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Application\Update;

use Dbapi\Blogging\Tag\Domain\TagId;
use Dbapi\Blogging\Tag\Domain\TagName;
use Dbapi\Blogging\Tag\Domain\TagRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;

final class UpdateTagCommandHandler implements CommandHandler
{
    public function __construct(private readonly TagRepository $repository) {}

    public function __invoke(UpdateTagCommand $command): void
    {
        $tag = $this->repository->search(new TagId($command->id()));

        if (null === $tag) {
            return;
        }

        $tag->rename(new TagName($command->name()));

        $this->repository->save($tag);
    }
}
