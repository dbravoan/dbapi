<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Application\Create;

use Dbapi\Blogging\Category\Domain\Category;
use Dbapi\Blogging\Category\Domain\CategoryId;
use Dbapi\Blogging\Category\Domain\CategoryName;
use Dbapi\Blogging\Category\Domain\CategoryRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;

final class CreateCategoryCommandHandler implements CommandHandler
{
    public function __construct(private readonly CategoryRepository $repository) {}

    public function __invoke(CreateCategoryCommand $command): void
    {
        $id = new CategoryId($command->id());
        $name = new CategoryName($command->name());
        $model = Category::create($id, $name);

        $this->repository->save($model);
    }
}
