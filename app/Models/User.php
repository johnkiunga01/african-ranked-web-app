<?php

namespace App\Models;

use App\Enums\StaffRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail, OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, \Laravel\Passport\HasApiTokens, Notifiable;

    protected $attributes = ['role' => 'author', 'is_active' => true, 'session_version' => 1, 'job_title' => 'Contributor'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token', 'two_factor_secret', 'two_factor_pending_secret', 'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_active && $this->hasVerifiedEmail() && $this->role === StaffRole::SuperAdmin;
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'removed_at' => 'datetime',
            'role' => StaffRole::class,
            'is_active' => 'boolean',
            'session_version' => 'integer',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted', 'two_factor_pending_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime', 'two_factor_pending_expires_at' => 'datetime',
            'two_factor_last_counter' => 'integer',
        ];
    }
}
