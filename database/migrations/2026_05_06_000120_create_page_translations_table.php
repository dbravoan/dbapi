<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_translations', function (Blueprint $table) {
            $table->uuid('page_id');
            $table->string('language_code', 2);

            $table->string('slug');
            $table->string('title');
            $table->json('content');

            $table->string('seo_title', 60)->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('canonical_url')->nullable();

            $table->string('og_title', 95)->nullable();
            $table->string('og_description', 200)->nullable();
            $table->string('og_image')->nullable();
            $table->json('structured_data')->nullable();

            $table->timestamps();

            $table->primary(['page_id', 'language_code']);
            $table->unique(['language_code', 'slug']);
            $table->index('language_code');
            $table->foreign('page_id')->references('id')->on('pages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_translations');
    }
};
