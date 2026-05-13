<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Shows full details for a single tenant.
 *
 * Usage:
 *   php artisan dba:tenant:show my_blog
 */
class ShowTenantCommand extends Command
{
    protected $signature = 'dba:tenant:show {app_id : Tenant identifier}';
    protected $description = 'Show details for a specific tenant';

    public function handle(): int
    {
        $appId  = strtolower(trim($this->argument('app_id')));
        $tenant = Tenant::where('app_id', $appId)->first();

        if ($tenant === null) {
            $this->error("Tenant '{$appId}' not found.");
            return self::FAILURE;
        }

        $this->line('');
        $this->line("<info>Tenant:</info> {$tenant->app_id} (id={$tenant->id})");
        $this->line("<info>Name:</info>       {$tenant->name}");
        $this->line("<info>Type:</info>       {$tenant->type}");
        $this->line("<info>Status:</info>     {$tenant->status}");
        $this->line("<info>Placement:</info>  {$tenant->placement}");
        $this->line("<info>Modules:</info>    " . implode(', ', $tenant->enabled_modules ?? []));
        $this->line("<info>Versions:</info>   " . ($tenant->allowed_versions ? implode(', ', $tenant->allowed_versions) : '(inherit global)'));
        $this->line("<info>Created:</info>    {$tenant->created_at}");
        $this->line("<info>Updated:</info>    {$tenant->updated_at}");
        $this->line('');

        return self::SUCCESS;
    }
}
