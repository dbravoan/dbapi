<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class DomainServiceProvider extends ServiceProvider
{
    private array $commandHandlers = [
        \Dbapi\Blogging\Category\Application\Create\CreateCategoryCommandHandler::class,
        \Dbapi\Blogging\Category\Application\Update\UpdateCategoryCommandHandler::class,
        \Dbapi\Language\Language\Application\Create\CreateLanguageCommandHandler::class,
        \Dbapi\Blogging\Post\Application\Create\CreatePostCommandHandler::class,
        \Dbapi\Blogging\Post\Application\Update\UpdatePostCommandHandler::class,
        \Dbapi\Blogging\Tag\Application\Create\CreateTagCommandHandler::class,
        \Dbapi\Blogging\Tag\Application\Update\UpdateTagCommandHandler::class,
        \Dbapi\Forms\Form\Application\Create\CreateFormCommandHandler::class,
        \Dbapi\Forms\Form\Application\Submit\SubmitFormCommandHandler::class,
        \Dbapi\PageManagement\Page\Application\Create\CreatePageCommandHandler::class,
        \Dbapi\PageManagement\Page\Application\Update\UpdatePageCommandHandler::class,
        \Dbapi\Identity\User\Application\Create\CreateUserCommandHandler::class,
        \Dbapi\Identity\User\Application\Update\UpdateUserCommandHandler::class,
        \Dbapi\TodoList\Task\Application\Create\CreateTaskCommandHandler::class,
        \Dbapi\TodoList\Task\Application\Update\UpdateTaskCommandHandler::class,
    ];

    private array $queryHandlers = [
        \Dbapi\Blogging\Category\Application\Find\FindCategoryQueryHandler::class,
        \Dbapi\Blogging\Category\Application\SearchByCriteria\SearchCategoriesByCriteriaQueryHandler::class,
        \Dbapi\Blogging\Category\Application\SearchByCriteria\CountCategoriesByCriteriaQueryHandler::class,
        \Dbapi\Blogging\Post\Application\Find\FindPostQueryHandler::class,
        \Dbapi\Blogging\Post\Application\SearchByCriteria\SearchPostsByCriteriaQueryHandler::class,
        \Dbapi\Blogging\Post\Application\SearchByCriteria\CountPostsByCriteriaQueryHandler::class,
        \Dbapi\Blogging\Tag\Application\Find\FindTagQueryHandler::class,
        \Dbapi\Blogging\Tag\Application\SearchByCriteria\SearchTagsByCriteriaQueryHandler::class,
        \Dbapi\Blogging\Tag\Application\SearchByCriteria\CountTagsByCriteriaQueryHandler::class,
        \Dbapi\Forms\Form\Application\Find\FindFormQueryHandler::class,
        \Dbapi\Identity\User\Application\Find\FindUserQueryHandler::class,
        \Dbapi\Language\Language\Application\Find\FindLanguageQueryHandler::class,
        \Dbapi\Language\Language\Application\FindAll\FindAllLanguagesQueryHandler::class,
        \Dbapi\PageManagement\Page\Application\Find\FindPageQueryHandler::class,
        \Dbapi\TodoList\Task\Application\Find\FindTaskQueryHandler::class,
    ];

    public function register(): void
    {
        foreach ($this->commandHandlers as $handler) {
            $this->app->tag($handler, 'dba_ddd.command_handler');
        }

        foreach ($this->queryHandlers as $handler) {
            $this->app->tag($handler, 'dba_ddd.query_handler');
        }
    }
}
