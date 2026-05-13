<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Application\Update;

use Dbapi\Blogging\Category\Domain\CategoryId;
use Dbapi\Blogging\Category\Domain\CategoryName;
use Dbapi\Blogging\Category\Domain\CategoryRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;

final class UpdateCategoryCommandHandler implements CommandHandler
{
    public function __construct(private readonly CategoryRepository $repository) {}

    public function __invoke(UpdateCategoryCommand $command): void
    {
        $category = $this->repository->search(new CategoryId($command->id()));

        if (null === $category) {
            return;
        }

        $category->rename(new CategoryName($command->name()));

        $this->repository->save($category);
    }
}
