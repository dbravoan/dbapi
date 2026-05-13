<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BlogPost extends Model
{
    /**
     * Non-translatable columns live here; translatable fields (title, slug,
     * content, SEO, OG) are stored in {tenant}_post_translations (see
     * BlogPostTranslation).
     */
    protected $fillable = ['id', 'category_id'];

    public $incrementing = false;
    protected $keyType = 'string';

    public function getTable(): string
    {
        $appId = config('database.tenant.app_id');
        return $appId ? $appId . '_posts' : 'posts';
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BlogPostTranslation::class, 'post_id', 'id');
    }
}
