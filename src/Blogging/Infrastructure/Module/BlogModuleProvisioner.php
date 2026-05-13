<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Infrastructure\Module;

use Dbapi\Shared\Infrastructure\Module\ModuleProvisioner;
use Illuminate\Database\Schema\Blueprint;

/**
 * Provisions / deprovisions all tables required by the Blog module for a tenant.
 *
 * Tables created:
 *   {app_id}_users
 *   {app_id}_categories
 *   {app_id}_tags
 *   {app_id}_posts
 *   {app_id}_post_translations
 *   {app_id}_post_tag  (pivot)
 */
final class BlogModuleProvisioner extends ModuleProvisioner
{
    public function moduleName(): string
    {
        return 'blog';
    }

    public function provision(string $appId): void
    {
        $this->provisionUsers($appId);
        $this->provisionCategories($appId);
        $this->provisionTags($appId);
        $this->provisionPosts($appId);
        $this->provisionPostTranslations($appId);
        $this->provisionPostTag($appId);
    }

    public function deprovision(string $appId): void
    {
        // Drop in reverse FK order
        $this->dropIfExists("{$appId}_post_tag");
        $this->dropIfExists("{$appId}_post_translations");
        $this->dropIfExists("{$appId}_posts");
        $this->dropIfExists("{$appId}_tags");
        $this->dropIfExists("{$appId}_categories");
        // Note: {app_id}_users is shared across modules — intentionally NOT dropped here
    }

    // -------------------------------------------------------------------------
    // Private table builders
    // -------------------------------------------------------------------------

    private function provisionUsers(string $appId): void
    {
        $this->createIfMissing("{$appId}_users", function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    private function provisionCategories(string $appId): void
    {
        $this->createIfMissing("{$appId}_categories", function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    private function provisionTags(string $appId): void
    {
        $this->createIfMissing("{$appId}_tags", function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    private function provisionPosts(string $appId): void
    {
        $this->createIfMissing("{$appId}_posts", function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id')->nullable();
            $table->timestamps();
        });

        // Idempotent column addition for existing tenants
        $this->addColumnIfMissing("{$appId}_posts", 'category_id', function (Blueprint $table) {
            $table->uuid('category_id')->nullable();
        });
    }

    private function provisionPostTranslations(string $appId): void
    {
        $this->createIfMissing("{$appId}_post_translations", function (Blueprint $table) {
            $table->uuid('post_id');
            $table->string('language_code', 5);
            $table->string('title');
            $table->string('slug');
            $table->text('content');

            // SEO per language
            $table->string('seo_title', 60)->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_title', 95)->nullable();
            $table->string('og_description', 200)->nullable();
            $table->string('og_image')->nullable();

            $table->timestamps();

            $table->unique(['post_id', 'language_code'], 'post_translations_post_lang_unique');
            $table->unique(['language_code', 'slug'], 'post_translations_lang_slug_unique');
        });
    }

    private function provisionPostTag(string $appId): void
    {
        $this->createIfMissing("{$appId}_post_tag", function (Blueprint $table) {
            $table->uuid('post_id');
            $table->uuid('tag_id');
            $table->primary(['post_id', 'tag_id']);
        });
    }
}
