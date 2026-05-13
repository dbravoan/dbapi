<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Infrastructure\Module;

use Dbapi\Shared\Infrastructure\Module\ModuleProvisioner;
use Illuminate\Database\Schema\Blueprint;

/**
 * Provisions / deprovisions all tables required by the Page Management module.
 *
 * Tables created:
 *   {app_id}_pages
 *   {app_id}_page_translations (intermediate translation table)
 */
final class PageModuleProvisioner extends ModuleProvisioner
{
    public function moduleName(): string
    {
        return 'pages';
    }

    public function provision(string $appId): void
    {
        $this->provisionPages($appId);
        $this->provisionPageTranslations($appId);
    }

    public function deprovision(string $appId): void
    {
        // Drop in reverse order
        $this->dropIfExists("{$appId}_page_translations");
        $this->dropIfExists("{$appId}_pages");
    }

    private function provisionPages(string $appId): void
    {
        $this->createIfMissing("{$appId}_pages", function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    private function provisionPageTranslations(string $appId): void
    {
        $this->createIfMissing("{$appId}_page_translations", function (Blueprint $table) {
            $table->uuid('page_id');
            $table->string('language_code', 2);

            // Common translatable fields
            $table->string('slug');
            $table->string('title');
            $table->json('content');

            // SEO fields (per language)
            $table->string('seo_title', 60)->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('canonical_url')->nullable();

            // Open Graph fields (per language)
            $table->string('og_title', 95)->nullable();
            $table->string('og_description', 200)->nullable();
            $table->string('og_image')->nullable();

            // Structured Data (JSON-LD)
            $table->json('structured_data')->nullable();

            $table->timestamps();

            // One translation per page and language
            $table->unique(['page_id', 'language_code'], 'page_translations_page_lang_unique');
            // Slug uniqueness by language (same slug can exist in another language)
            $table->unique(['language_code', 'slug'], 'page_translations_lang_slug_unique');
        });
    }
}
