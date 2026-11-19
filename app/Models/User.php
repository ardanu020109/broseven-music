<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_LISTENER = 'listener';

    /**
     * role dan is_active TIDAK mass-assignable: hanya diisi eksplisit oleh service/seeder.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isListener(): bool
    {
        return $this->role === self::ROLE_LISTENER;
    }

    public function homePath(): string
    {
        return $this->isSuperadmin() ? '/admin/dashboard' : '/home';
    }

    public function scopeListeners(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_LISTENER);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function artists(): HasMany
    {
        return $this->hasMany(Artist::class);
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class);
    }

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteSongs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class, 'favorites')->withTimestamps();
    }
}