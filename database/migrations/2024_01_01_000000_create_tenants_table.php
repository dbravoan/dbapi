<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('app_id', 63)->unique()->comment('URL-safe tenant identifier: lowercase, letters/numbers/hyphen/underscore');
            $table->string('name')->comment('Human-readable display name');
            $table->enum('type', ['blog', 'game', 'shop', 'todolist', 'calendar'])->default('blog')->comment('Primary product type');
            $table->enum('status', ['active', 'suspended', 'archived'])->default('active');
            $table->json('enabled_modules')->nullable()->comment('Array of enabled module names, e.g. ["blog","todolist"]');
            $table->json('allowed_versions')->nullable()->comment('Null = inherit global config. Array overrides, e.g. ["v1","v2"]');
            $table->enum('placement', ['pooled', 'dedicated'])->default('pooled')->comment('pooled = shared DB, dedicated = own server/DB');
            $table->string('dedicated_db_connection')->nullable()->comment('Laravel DB connection name for dedicated tenants');
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
