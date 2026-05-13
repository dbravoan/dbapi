<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class BlogTag extends Model
{
    protected $fillable = ['id', 'name', 'slug'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function getTable(): string
    {
        $appId = config('database.tenant.app_id');
        return $appId ? $appId . '_tags' : 'tags';
    }
}
