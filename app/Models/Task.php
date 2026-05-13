<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Task extends Model
{
    protected $fillable = ['id', 'title', 'status', 'priority', 'description'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function getTable(): string
    {
        $appId = config('database.tenant.app_id');
        return $appId ? $appId . '_tasks' : 'tasks';
    }
}
