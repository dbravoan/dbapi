<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Dbapi\Blogging\Infrastructure\Module\BlogModuleProvisioner;
use Dbapi\Forms\Infrastructure\Module\FormsModuleProvisioner;
use Dbapi\Language\Language\Infrastructure\Module\LanguageModuleProvisioner;
use Dbapi\PageManagement\Page\Infrastructure\Module\PageModuleProvisioner;
use Dbapi\TodoList\Task\Infrastructure\Module\TodoListModuleProvisioner;
use Illuminate\Console\Command;

/**
 * Enables a module for an existing tenant and provisions its tables if needed.
 *
 * Usage:
 *   php artisan dba:tenant:enable-module my_blog blog
 *   php artisan dba:tenant:enable-module my_app todolist
 *   php artisan dba:tenant:enable-module my_app pages
 *   php artisan dba:tenant:enable-module my_app languages
 *   php artisan dba:tenant:enable-module my_app forms
 */
class EnableModuleCommand extends Command
{
    protected $signature = 'dba:tenant:enable-module
        {app_id  : Tenant identifier}
        {module  : Module to enable: blog|todolist|pages|languages|forms|shop|calendar|game}';

    protected $description = 'Enable a module for an existing tenant and provision its tables';

    public function __construct(
        private readonly BlogModuleProvisioner $blogProvisioner,
        private readonly TodoListModuleProvisioner $todoListProvisioner,
        private readonly PageModuleProvisioner $pageProvisioner,
        private readonly LanguageModuleProvisioner $languageProvisioner,
        private readonly FormsModuleProvisioner $formsProvisioner,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $appId  = strtolower(trim($this->argument('app_id')));
        $module = strtolower(trim($this->argument('module')));

        $tenant = Tenant::where('app_id', $appId)->first();

        if ($tenant === null) {
            $this->error("Tenant '{$appId}' not found.");
            return self::FAILURE;
        }

        if ($tenant->hasModule($module)) {
            $this->warn("Module '{$module}' is already enabled for tenant '{$appId}'.");
            return self::SUCCESS;
        }

        $tenant->enableModule($module);
        $tenant->save();

        $this->info("Module <comment>{$module}</comment> enabled for tenant <comment>{$appId}</comment>.");
        $this->line("  Provisioning tables...");

        match ($module) {
            'blog'     => $this->blogProvisioner->provision($appId),
            'todolist' => $this->todoListProvisioner->provision($appId),
            'pages'    => $this->pageProvisioner->provision($appId),
            'languages'=> $this->languageProvisioner->provision($appId),
            'forms'    => $this->formsProvisioner->provision($appId),
            default    => $this->warn("  No provisioner for module '{$module}' — tables not created."),
        };

        $this->info("  Done.");

        return self::SUCCESS;
    }
}
