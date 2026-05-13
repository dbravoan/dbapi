<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Form extends Model
{
    protected $fillable = [
        'key',
        'name',
        'recipient_email',
        'fields',
        'active',
    ];

    protected $casts = [
        'fields' => 'array',
        'active' => 'boolean',
    ];

    public function getTable(): string
    {
        $appId = config('database.tenant.app_id');
        return $appId ? $appId . '_forms' : 'forms';
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id');
    }
}
