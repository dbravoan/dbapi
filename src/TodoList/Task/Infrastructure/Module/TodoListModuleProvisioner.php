<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Infrastructure\Module;

use Dbapi\Shared\Infrastructure\Module\ModuleProvisioner;
use Illuminate\Database\Schema\Blueprint;

/**
 * Provisions / deprovisions all tables required by the TodoList module.
 *
 * Tables created:
 *   {app_id}_tasks
 */
final class TodoListModuleProvisioner extends ModuleProvisioner
{
    public function moduleName(): string
    {
        return 'todolist';
    }

    public function provision(string $appId): void
    {
        $this->createIfMissing("{$appId}_tasks", function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function deprovision(string $appId): void
    {
        $this->dropIfExists("{$appId}_tasks");
    }
}
