<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * NOT marked final: Laravel Passport extends this model at runtime via the
 * `auth.providers.users.model` binding and the `HasApiTokens` trait creates
 * generated relationship classes that reference subclasses. UserFactory also
 * lives in database/factories/UserFactory.php and relies on this class being
 * extendable.
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getTable(): string
    {
        $appId = config('database.tenant.app_id');

        if ($appId) {
            return $appId . '_users';
        }

        return parent::getTable();
    }
}
