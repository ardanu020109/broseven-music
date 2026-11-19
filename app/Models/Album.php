<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    protected $fillable = [
        'artist_id',
        'name',
        'cover_image',
        'release_year',
        'description',
    ];

    protected $hidden = [
        'cover_image',
    ];

    protected $appends = [
        'cover_url',
    ];

    protected function casts(): array
    {
        return [
            'release_year' => 'integer',
        ];
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => $this->cover_image
            ? route('media.album.cover', ['album' => $this->getKey()], false).'?v='.($this->updated_at?->timestamp ?? 0)
            : null);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class)->orderBy('track_number');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(filled($term), fn (Builder $q) => $q->where('name', 'like', '%'.$term.'%'));
    }
}