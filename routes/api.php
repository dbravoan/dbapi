<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Dbapi\Blogging\Post\Infrastructure\Controller\CreatePostController;
use Dbapi\Blogging\Post\Infrastructure\Controller\FindPostController;
use Dbapi\Blogging\Post\Infrastructure\Controller\SearchPostsByCriteriaController;
use Dbapi\Blogging\Post\Infrastructure\Controller\UpdatePostController;
use Dbapi\Blogging\Category\Infrastructure\Controller\CreateCategoryController;
use Dbapi\Blogging\Category\Infrastructure\Controller\FindCategoryController;
use Dbapi\Blogging\Category\Infrastructure\Controller\SearchCategoriesByCriteriaController;
use Dbapi\Blogging\Category\Infrastructure\Controller\UpdateCategoryController;
use Dbapi\Blogging\Tag\Infrastructure\Controller\CreateTagController;
use Dbapi\Blogging\Tag\Infrastructure\Controller\FindTagController;
use Dbapi\Blogging\Tag\Infrastructure\Controller\SearchTagsByCriteriaController;
use Dbapi\Blogging\Tag\Infrastructure\Controller\UpdateTagController;
use Dbapi\Forms\Form\Infrastructure\Controller\CreateFormController;
use Dbapi\Forms\Form\Infrastructure\Controller\FindFormController;
use Dbapi\Forms\Form\Infrastructure\Controller\SubmitFormController;
use Dbapi\Identity\User\Infrastructure\Controller\CreateUserController;
use Dbapi\Identity\User\Infrastructure\Controller\FindUserController;
use Dbapi\Identity\User\Infrastructure\Controller\UpdateUserController;
use Dbapi\Language\Language\Infrastructure\Controller\CreateLanguageController;
use Dbapi\Language\Language\Infrastructure\Controller\FindAllLanguagesController;
use Dbapi\Language\Language\Infrastructure\Controller\FindLanguageController;
use Dbapi\PageManagement\Page\Infrastructure\Controller\CreatePageController;
use Dbapi\PageManagement\Page\Infrastructure\Controller\FindPageController;
use Dbapi\PageManagement\Page\Infrastructure\Controller\UpdatePageController;
use Dbapi\TodoList\Task\Infrastructure\Controller\CreateTaskController;
use Dbapi\TodoList\Task\Infrastructure\Controller\FindTaskController;
use Dbapi\TodoList\Task\Infrastructure\Controller\UpdateTaskController;

Route::prefix('{tenant}/{version}')
    ->middleware(['identify_tenant', 'api.version', 'tenant'])
    ->group(function () {

        // --------------------------------------------------------------------
        // Blog module
        // --------------------------------------------------------------------
        Route::middleware('require.module:blog')->group(function () {

            // Read — open
            Route::get('/posts',           SearchPostsByCriteriaController::class);
            Route::get('/posts/{id}',      FindPostController::class);
            Route::get('/categories',      SearchCategoriesByCriteriaController::class);
            Route::get('/categories/{id}', FindCategoryController::class);
            Route::get('/tags',            SearchTagsByCriteriaController::class);
            Route::get('/tags/{id}',       FindTagController::class);

            // Write — authenticated
            Route::middleware('auth:api')->group(function () {
                Route::post('/posts',           CreatePostController::class);
                Route::put('/posts/{id}',       UpdatePostController::class);

                Route::post('/categories',      CreateCategoryController::class);
                Route::put('/categories/{id}',  UpdateCategoryController::class);

                Route::post('/tags',            CreateTagController::class);
                Route::put('/tags/{id}',        UpdateTagController::class);
            });
        });

        // --------------------------------------------------------------------
        // Forms module
        // --------------------------------------------------------------------
        Route::middleware('require.module:forms')->group(function () {
            Route::post('/forms/{key}/submit', SubmitFormController::class);

            Route::middleware('auth:api')->group(function () {
                Route::post('/forms', CreateFormController::class);
                Route::get('/forms/{id}', FindFormController::class);
            });
        });

        // --------------------------------------------------------------------
        // Identity (users) — no module gate; users are cross-module
        // --------------------------------------------------------------------
        Route::get('/users/{id}', FindUserController::class);

        Route::middleware('auth:api')->group(function () {
            Route::post('/users',      CreateUserController::class);
            Route::put('/users/{id}',  UpdateUserController::class);

            Route::get('/user', function (Request $request) {
                return $request->user();
            });
        });

        // --------------------------------------------------------------------
        // Languages module
        // --------------------------------------------------------------------
        Route::middleware('require.module:languages')->group(function () {
            Route::get('/languages',      FindAllLanguagesController::class);
            Route::get('/languages/{id}', FindLanguageController::class);

            Route::middleware('auth:api')->group(function () {
                Route::post('/languages', CreateLanguageController::class);
            });
        });

        // --------------------------------------------------------------------
        // Page Management module
        // --------------------------------------------------------------------
        Route::middleware('require.module:pages')->group(function () {

            Route::get('/pages/{id}', FindPageController::class);

            Route::middleware('auth:api')->group(function () {
                Route::post('/pages',      CreatePageController::class);
                Route::put('/pages/{id}',  UpdatePageController::class);
            });
        });

        // --------------------------------------------------------------------
        // TodoList module
        // --------------------------------------------------------------------
        Route::middleware('require.module:todolist')->group(function () {

            Route::get('/tasks/{id}', FindTaskController::class);

            Route::middleware('auth:api')->group(function () {
                Route::post('/tasks',      CreateTaskController::class);
                Route::put('/tasks/{id}',  UpdateTaskController::class);
            });
        });
    });
