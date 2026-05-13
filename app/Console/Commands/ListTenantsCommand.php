<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Lists all tenants with their key attributes.
 *
 * Usage:
 *   php artisan dba:tenant:list
 *   php artisan dba:tenant:list --status=active
 */
class ListTenantsCommand extends Command
{
    protected $signature = 'dba:tenant:list
        {--status= : Filter by status: active|suspended|archived}';

    protected $description = 'List all registered tenants';

    public function handle(): int
    {
        $query = Tenant::query()->orderBy('app_id');

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found.');
            return self::SUCCESS;
        }

        $this->table(
            ['app_id', 'name', 'type', 'status', 'modules', 'placement'],
            $tenants->map(fn(Tenant $t) => [
                $t->app_id,
                $t->name,
                $t->type,
                $t->status,
                implode(', ', $t->enabled_modules ?? []),
                $t->placement,
            ])->toArray()
        );

        $this->line('Total: ' . $tenants->count());

        return self::SUCCESS;
    }
}
