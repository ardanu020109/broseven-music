<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Song extends Model
{
    protected $fillable = [
        'album_id',
        'artist_id',
        'title',
        'audio_path',
        'duration',
        'file_size',
        'mime_type',
        'lyrics',
        'track_number',
    ];

    /**
     * Path file audio tidak boleh bocor ke frontend.
     */
    protected $hidden = [
        'audio_path',
    ];

    protected $appends = [
        'stream_url',
        'download_url',
        'cover_url',
    ];

    protected function casts(): array
    {
        return [
            'duration'     => 'integer',
            'file_size'    => 'integer',
            'track_number' => 'integer',
        ];
    }

    protected function streamUrl(): Attribute
    {
        return Attribute::get(fn () => $this->exists
            ? route('music.stream', ['song' => $this->getKey()], false)
            : null);
    }

    protected function downloadUrl(): Attribute
    {
        return Attribute::get(fn () => $this->exists
            ? route('music.download', ['song' => $this->getKey()], false)
            : null);
    }

    /**
     * Cover lagu = cover album (hanya jika relasi album sudah dimuat).
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => $this->relationLoaded('album') ? $this->album?->cover_url : null);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class, 'playlist_song')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * Menambahkan atribut boolean is_favorite untuk user tertentu.
     */
    public function scopeWithFavoriteFlag(Builder $query, int $userId): Builder
    {
        return $query->withExists([
            'favorites as is_favorite' => fn ($q) => $q->where('user_id', $userId),
        ]);
    }
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(filled($term), fn (Builder $q) => $q->where('title', 'like', '%'.$term.'%'));
    }
}
