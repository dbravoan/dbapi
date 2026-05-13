<?php

declare(strict_types=1);

namespace App\Providers;

use Dbapi\Identity\User\Domain\UserRepository;
use Dbapi\Identity\User\Infrastructure\Persistence\EloquentUserRepository;
use Dbapi\Language\Language\Domain\LanguageRepository;
use Dbapi\Language\Language\Infrastructure\Persistence\EloquentLanguageRepository;
use Dbapi\Blogging\Post\Domain\PostRepository;
use Dbapi\Blogging\Post\Infrastructure\Persistence\EloquentPostRepository;
use Dbapi\Blogging\Category\Domain\CategoryRepository;
use Dbapi\Blogging\Category\Infrastructure\Persistence\EloquentCategoryRepository;
use Dbapi\PageManagement\Page\Domain\PageRepository;
use Dbapi\PageManagement\Page\Infrastructure\Persistence\EloquentPageRepository;
use Dbapi\Blogging\Tag\Domain\TagRepository;
use Dbapi\Blogging\Tag\Infrastructure\Persistence\EloquentTagRepository;
use Dbapi\Forms\Form\Application\Create\CreatedFormIdHolder;
use Dbapi\Forms\Form\Domain\FormRepository;
use Dbapi\Forms\Form\Infrastructure\Persistence\EloquentFormRepository;
use Dbapi\TodoList\Task\Domain\TaskRepository;
use Dbapi\TodoList\Task\Infrastructure\Persistence\EloquentTaskRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Event\EventBus;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, function ($app) {
            return new EloquentUserRepository(new \App\Models\User(), $app->make(EventBus::class));
        });

        $this->app->bind(PostRepository::class, function ($app) {
            return new EloquentPostRepository(new \App\Models\BlogPost(), $app->make(EventBus::class));
        });

        $this->app->bind(CategoryRepository::class, function ($app) {
            return new EloquentCategoryRepository(new \App\Models\BlogCategory(), $app->make(EventBus::class));
        });

        $this->app->bind(TagRepository::class, function ($app) {
            return new EloquentTagRepository(new \App\Models\BlogTag(), $app->make(EventBus::class));
        });

        $this->app->bind(TaskRepository::class, function ($app) {
            return new EloquentTaskRepository(new \App\Models\Task(), $app->make(EventBus::class));
        });

        $this->app->bind(LanguageRepository::class, function ($app) {
            return new EloquentLanguageRepository(new \App\Models\Language(), $app->make(EventBus::class));
        });

        $this->app->bind(PageRepository::class, function ($app) {
            return new EloquentPageRepository(new \App\Models\Page(), $app->make(EventBus::class));
        });

        $this->app->bind(FormRepository::class, function ($app) {
            return new EloquentFormRepository(new \App\Models\Form(), $app->make(EventBus::class));
        });

        // Request-scoped holder: the CreateFormCommandHandler writes the persisted
        // id into it and the CreateFormController reads it back. One instance per
        // HTTP request, so submissions for different requests do not bleed into
        // each other.
        $this->app->scoped(CreatedFormIdHolder::class, fn () => new CreatedFormIdHolder());
    }
}
