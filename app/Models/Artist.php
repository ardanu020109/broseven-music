<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artist extends Model
{
    protected $fillable = [
        'name',
        'biography',
        'photo',
    ];

    /**
     * Path file tidak dikirim ke frontend; frontend memakai photo_url.
     */
    protected $hidden = [
        'photo',
    ];

    protected $appends = [
        'photo_url',
    ];

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo
            ? route('media.artist.photo', ['artist' => $this->getKey()], false).'?v='.($this->updated_at?->timestamp ?? 0)
            : null);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(filled($term), fn (Builder $q) => $q->where('name', 'like', '%'.$term.'%'));
    }
}