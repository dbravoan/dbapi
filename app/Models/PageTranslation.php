<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PageTranslation extends Model
{
    protected $fillable = [
        'page_id',
        'language_code',
        'slug',
        'title',
        'content',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'structured_data',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'content'         => 'array',
        'structured_data' => 'array',
    ];

    public function getTable(): string
    {
        $appId = config('database.tenant.app_id');
        return $appId ? $appId . '_page_translations' : 'page_translations';
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}
