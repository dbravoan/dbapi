<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $app_id
 * @property string $name
 * @property string $type  blog|game|shop|todolist|calendar
 * @property string $status  active|suspended|archived
 * @property array|null $enabled_modules
 * @property array|null $allowed_versions
 * @property string $placement  pooled|dedicated
 * @property string|null $dedicated_db_connection
 */
final class Tenant extends Model
{
    protected $fillable = [
        'app_id',
        'name',
        'type',
        'status',
        'enabled_modules',
        'allowed_versions',
        'placement',
        'dedicated_db_connection',
    ];

    protected $casts = [
        'enabled_modules' => 'array',
        'allowed_versions' => 'array',
    ];

    // -------------------------------------------------------------------------
    // Status helpers
    // -------------------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    // -------------------------------------------------------------------------
    // Module helpers
    // -------------------------------------------------------------------------

    public function hasModule(string $module): bool
    {
        $modules = $this->enabled_modules ?? [];
        return in_array($module, $modules, true);
    }

    public function enableModule(string $module): void
    {
        $modules = $this->enabled_modules ?? [];
        if (!in_array($module, $modules, true)) {
            $modules[] = $module;
        }
        $this->enabled_modules = $modules;
    }

    public function disableModule(string $module): void
    {
        $modules = $this->enabled_modules ?? [];
        $this->enabled_modules = array_values(array_filter($modules, fn($m) => $m !== $module));
    }

    // -------------------------------------------------------------------------
    // Version helpers
    // -------------------------------------------------------------------------

    /** Returns the versions this tenant allows, or null to inherit global config. */
    public function allowedVersions(): ?array
    {
        return $this->allowed_versions;
    }
}
