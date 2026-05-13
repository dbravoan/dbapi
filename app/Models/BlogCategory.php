<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class BlogCategory extends Model
{
    protected $fillable = ['id', 'name', 'slug'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function getTable(): string
    {
        $appId = config('database.tenant.app_id');
        return $appId ? $appId . '_categories' : 'categories';
    }
}
