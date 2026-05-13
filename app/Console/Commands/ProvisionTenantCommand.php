<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class ProvisionTenantCommand extends Command
{
    protected $signature = 'dba:tenant:provision {app_id : The unique ID of the application/tenant}';
    protected $description = 'Provision database tables for a new tenant';

    public function handle()
    {
        $appId = $this->argument('app_id');

        $this->info("Provisioning tenant: {$appId}");

        $this->provisionUsersTable($appId);
        $this->provisionCategoriesTable($appId);
        $this->provisionTagsTable($appId);
        $this->provisionPostsTable($appId);
        $this->provisionPostTagsTable($appId);

        $this->info("Tenant {$appId} provisioned successfully.");
    }

    private function provisionUsersTable(string $appId)
    {
        $tableName = "{$appId}_users";

        if (Schema::hasTable($tableName)) {
            $this->warn("Table {$tableName} already exists. Skipping.");
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        $this->line("<info>Created table:</info> {$tableName}");
    }

    private function provisionCategoriesTable(string $appId)
    {
        $tableName = "{$appId}_categories";
        if (Schema::hasTable($tableName)) return;

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        $this->line("<info>Created table:</info> {$tableName}");
    }

    private function provisionTagsTable(string $appId)
    {
        $tableName = "{$appId}_tags";
        if (Schema::hasTable($tableName)) return;

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        $this->line("<info>Created table:</info> {$tableName}");
    }

    private function provisionPostsTable(string $appId)
    {
        $tableName = "{$appId}_posts";

        if (Schema::hasTable($tableName)) {
            // Drop and recreate if we want to ensure new structure, but here we just warn or use Schema::table
            // For now, let's just create if not exists
        } else {
            Schema::create($tableName, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('content');
                $table->string('language', 5);
                $table->uuid('category_id')->nullable();
                $table->timestamps();
            });
            $this->line("<info>Created table:</info> {$tableName}");
        }

        // Add category_id if missing (for existing tenants)
        if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'category_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->uuid('category_id')->nullable()->after('language');
            });
            $this->line("<info>Updated table:</info> {$tableName} with category_id");
        }
    }

    private function provisionPostTagsTable(string $appId)
    {
        $tableName = "{$appId}_post_tag";
        if (Schema::hasTable($tableName)) return;

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('post_id');
            $table->uuid('tag_id');
            $table->primary(['post_id', 'tag_id']);
        });
        $this->line("<info>Created table:</info> {$tableName}");
    }
}
