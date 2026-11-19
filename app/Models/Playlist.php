<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Playlist extends Model
{
    protected $fillable = [
        'name',
        'description',
        'cover_image',
    ];

    protected $hidden = [
        'cover_image',
    ];

    protected $appends = [
        'cover_url',
    ];

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => $this->cover_image
            ? route('media.playlist.cover', ['playlist' => $this->getKey()], false).'?v='.($this->updated_at?->timestamp ?? 0)
            : null);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class, 'playlist_song')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(filled($term), fn (Builder $q) => $q->where('name', 'like', '%'.$term.'%'));
    }
}