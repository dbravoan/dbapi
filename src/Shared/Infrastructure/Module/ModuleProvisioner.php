<?php

declare(strict_types=1);

namespace Dbapi\Shared\Infrastructure\Module;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base class for all module provisioners.
 * Provides idempotent helpers for schema management.
 */
abstract class ModuleProvisioner
{
    /** Module name — must match the value stored in tenants.enabled_modules. */
    abstract public function moduleName(): string;

    /** Create all tables required by this module for the given tenant. */
    abstract public function provision(string $appId): void;

    /** Drop all tables created by this module for the given tenant. */
    abstract public function deprovision(string $appId): void;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function createIfMissing(string $table, callable $blueprint): void
    {
        if (!Schema::hasTable($table)) {
            Schema::create($table, $blueprint);
        }
    }

    protected function addColumnIfMissing(string $table, string $column, callable $blueprint): void
    {
        if (Schema::hasTable($table) && !Schema::hasColumn($table, $column)) {
            Schema::table($table, $blueprint);
        }
    }

    protected function dropIfExists(string $table): void
    {
        Schema::dropIfExists($table);
    }
}
