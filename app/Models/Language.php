<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Language extends Model
{
    protected $fillable = ['id', 'code', 'name', 'native_name', 'is_default', 'is_active'];
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function getTable(): string
    {
        $appId = config('database.tenant.app_id');
        return $appId ? $appId . '_languages' : 'languages';
    }
}
