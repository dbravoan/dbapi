<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Page extends Model
{
    protected $fillable = ['id', 'status'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function getTable(): string
    {
        $appId = config('database.tenant.app_id');
        return $appId ? $appId . '_pages' : 'pages';
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class, 'page_id');
    }
}
