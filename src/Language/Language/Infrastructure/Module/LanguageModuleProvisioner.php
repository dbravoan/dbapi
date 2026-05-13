<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Infrastructure\Module;

use Dbapi\Shared\Infrastructure\Module\ModuleProvisioner;
use Illuminate\Database\Schema\Blueprint;

/**
 * Provisions / deprovisions all tables required by the Language module.
 *
 * Tables created:
 *   {app_id}_languages
 */
final class LanguageModuleProvisioner extends ModuleProvisioner
{
    public function moduleName(): string
    {
        return 'languages';
    }

    public function provision(string $appId): void
    {
        $this->createIfMissing("{$appId}_languages", function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 2)->unique();
            $table->string('name');
            $table->string('native_name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function deprovision(string $appId): void
    {
        $this->dropIfExists("{$appId}_languages");
    }
}
