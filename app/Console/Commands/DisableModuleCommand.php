<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Disables a module for an existing tenant (soft disable — does NOT drop tables).
 *
 * Usage:
 *   php artisan dba:tenant:disable-module my_blog blog
 */
class DisableModuleCommand extends Command
{
    protected $signature = 'dba:tenant:disable-module
        {app_id  : Tenant identifier}
        {module  : Module to disable}
        {--drop-tables : Also drop all tables for this module (DESTRUCTIVE)}';

    protected $description = 'Disable a module for an existing tenant (tables are kept by default)';

    public function handle(): int
    {
        $appId  = strtolower(trim($this->argument('app_id')));
        $module = strtolower(trim($this->argument('module')));

        $tenant = Tenant::where('app_id', $appId)->first();

        if ($tenant === null) {
            $this->error("Tenant '{$appId}' not found.");
            return self::FAILURE;
        }

        if (!$tenant->hasModule($module)) {
            $this->warn("Module '{$module}' is not enabled for tenant '{$appId}'.");
            return self::SUCCESS;
        }

        $tenant->disableModule($module);
        $tenant->save();

        $this->info("Module <comment>{$module}</comment> disabled for tenant <comment>{$appId}</comment>.");
        $this->line("  Tables are kept. Use <comment>--drop-tables</comment> to remove them.");

        return self::SUCCESS;
    }
}
