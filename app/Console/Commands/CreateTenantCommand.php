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
 * Creates a new tenant record and provisions its requested modules.
 *
 * Usage:
 *   php artisan dba:tenant:create my_blog --name="My Blog" --type=blog --modules=blog
 *   php artisan dba:tenant:create demo_app --name="Demo" --type=blog --modules=blog,todolist,pages,languages,forms
 */
class CreateTenantCommand extends Command
{
    protected $signature = 'dba:tenant:create
        {app_id             : Unique URL-safe identifier (lowercase, letters/numbers/hyphen/underscore)}
        {--name=            : Human-readable display name (defaults to app_id)}
        {--type=blog        : Primary product type: blog|game|shop|todolist|calendar}
        {--modules=         : Comma-separated list of modules to enable, e.g. blog,todolist,pages,languages,forms}
        {--versions=        : Comma-separated allowed API versions (blank = inherit global)}';

    protected $description = 'Create a new tenant and provision its modules';

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
        $appId = strtolower(trim($this->argument('app_id')));

        if (!preg_match('/^[a-z0-9][a-z0-9_-]{1,61}[a-z0-9]$/', $appId)) {
            $this->error("Invalid app_id '{$appId}'. Use 3-63 lowercase chars, numbers, hyphens or underscores.");
            return self::FAILURE;
        }

        if (Tenant::where('app_id', $appId)->exists()) {
            $this->error("Tenant '{$appId}' already exists.");
            return self::FAILURE;
        }

        $name    = $this->option('name') ?: $appId;
        $type    = $this->option('type') ?: 'blog';
        $modules = $this->parseList($this->option('modules'));
        $versions = $this->parseList($this->option('versions'));

        $validTypes = ['blog', 'game', 'shop', 'todolist', 'calendar'];
        if (!in_array($type, $validTypes, true)) {
            $this->error("Invalid type '{$type}'. Allowed: " . implode(', ', $validTypes));
            return self::FAILURE;
        }

        $tenant = Tenant::create([
            'app_id'           => $appId,
            'name'             => $name,
            'type'             => $type,
            'status'           => 'active',
            'enabled_modules'  => $modules ?: null,
            'allowed_versions' => $versions ?: null,
            'placement'        => 'pooled',
        ]);

        $this->info("Tenant <comment>{$appId}</comment> created (id={$tenant->id}).");

        // Provision each requested module
        foreach ($modules as $module) {
            $this->provisionModule($appId, $module);
        }

        $this->info("Done. Tenant <comment>{$appId}</comment> is ready.");

        return self::SUCCESS;
    }

    private function provisionModule(string $appId, string $module): void
    {
        $this->line("  Provisioning module: <info>{$module}</info>");

        match ($module) {
            'blog'     => $this->blogProvisioner->provision($appId),
            'todolist' => $this->todoListProvisioner->provision($appId),
            'pages'    => $this->pageProvisioner->provision($appId),
            'languages'=> $this->languageProvisioner->provision($appId),
            'forms'    => $this->formsProvisioner->provision($appId),
            default    => $this->warn("  Unknown module '{$module}' — skipped."),
        };
    }

    private function parseList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $value))
        ));
    }
}
