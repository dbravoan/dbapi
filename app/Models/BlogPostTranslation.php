<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BlogPostTranslation extends Model
{
    protected $fillable = [
        'post_id',
        'language_code',
        'title',
        'slug',
        'content',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
    ];

    protected $casts = [
        'content' => 'string',
    ];

    public function getTable(): string
    {
        $appId = config('database.tenant.app_id');
        return $appId ? $appId . '_post_translations' : 'post_translations';
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'post_id', 'id');
    }
}
