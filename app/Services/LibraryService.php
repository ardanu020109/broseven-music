<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Kueri koleksi listener untuk Beranda, pencarian, dan antrean putar.
 */
class LibraryService
{
    public const QUEUE_LIMIT = 500;

    public function stats(User $user): array
    {
        return [
            'songs'     => $user->songs()->count(),
            'albums'    => $user->albums()->count(),
            'artists'   => $user->artists()->count(),
            'playlists' => $user->playlists()->count(),
            'favorites' => $user->favorites()->count(),
        ];
    }

    public function sections(User $user): array
    {
        return [
            'recentlyAdded' => $this->songsQuery($user)
                ->orderByDesc('songs.created_at')->orderByDesc('songs.id')
                ->limit(10)->get(),
            'recentlyPlayed' => $this->songsQuery($user)
                ->whereNotNull('songs.last_played_at')
                ->orderByDesc('songs.last_played_at')->orderByDesc('songs.id')
                ->limit(10)->get(),
            'albums' => $user->albums()
                ->with('artist:id,name')->withCount('songs')
                ->orderByDesc('albums.created_at')->orderByDesc('albums.id')
                ->limit(10)->get(),
            'artists' => $user->artists()
                ->withCount(['albums', 'songs'])
                ->orderByDesc('artists.created_at')->orderByDesc('artists.id')
                ->limit(10)->get(),
            'playlists' => $user->playlists()
                ->withCount('songs')
                ->orderByDesc('playlists.created_at')->orderByDesc('playlists.id')
                ->limit(10)->get(),
            'favorites' => $this->favoriteSongs($user, null, 5),
            'allSongs'  => $this->songsQuery($user)
                ->orderBy('songs.title')->orderBy('songs.id')
                ->limit(8)->get(),
        ];
    }

    /**
     * Pencarian lintas kategori. Tanda % dan _ diperlakukan sebagai teks biasa.
     */
    public function search(User $user, string $term): array
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $songs = $this->songsQuery($user)
            ->where(function ($q) use ($like) {
                $q->where('songs.title', 'like', $like)
                    ->orWhereHas('artist', fn ($a) => $a->where('name', 'like', $like))
                    ->orWhereHas('album', fn ($a) => $a->where('name', 'like', $like));
            })
            ->orderBy('songs.title')->orderBy('songs.id')
            ->limit(30)->get();

        $albums = $user->albums()
            ->where(function ($q) use ($like) {
                $q->where('albums.name', 'like', $like)
                    ->orWhereHas('artist', fn ($a) => $a->where('name', 'like', $like));
            })
            ->with('artist:id,name')->withCount('songs')
            ->orderBy('albums.name')
            ->limit(12)->get();

        $artists = $user->artists()
            ->where('artists.name', 'like', $like)
            ->withCount(['albums', 'songs'])
            ->orderBy('artists.name')
            ->limit(12)->get();

        $playlists = $user->playlists()
            ->where('playlists.name', 'like', $like)
            ->withCount('songs')
            ->orderBy('playlists.name')
            ->limit(12)->get();

        return compact('songs', 'albums', 'artists', 'playlists');
    }

    /**
     * Semua lagu (untuk antrean). Urutan sama dengan halaman My Songs.
     */
    public function allSongs(User $user, array $filters = [], int $limit = self::QUEUE_LIMIT): Collection
    {
        return $this->songsQuery($user)
            ->search($filters['search'] ?? null)
            ->when(! empty($filters['album_id']), fn (Builder $q) => $q->where('songs.album_id', (int) $filters['album_id']))
            ->when(! empty($filters['artist_id']), fn (Builder $q) => $q->where('songs.artist_id', (int) $filters['artist_id']))
            ->orderBy('songs.title')->orderBy('songs.id')
            ->limit($limit)->get();
    }

    /**
     * Lagu album berdasarkan track_number (tanpa nomor di paling bawah).
     */
    public function albumSongs(Album $album, User $user): Collection
    {
        return $this->songsQuery($user)
            ->where('songs.album_id', $album->id)
            ->orderByRaw('songs.track_number IS NULL')
            ->orderBy('songs.track_number')
            ->orderBy('songs.title')
            ->orderBy('songs.id')
            ->get();
    }

    public function artistSongs(Artist $artist, User $user): Collection
    {
        return $this->songsQuery($user)
            ->where('songs.artist_id', $artist->id)
            ->orderBy('songs.title')->orderBy('songs.id')
            ->get();
    }

    /**
     * Favorit, terbaru difavoritkan lebih dulu (sama dengan halaman Favorites).
     */
    public function favoriteSongs(User $user, ?string $search = null, int $limit = self::QUEUE_LIMIT): Collection
    {
        return Song::query()
            ->select('songs.*')
            ->join('favorites', 'favorites.song_id', '=', 'songs.id')
            ->where('favorites.user_id', $user->id)
            ->where('songs.user_id', $user->id)
            ->when(filled($search), fn (Builder $q) => $q->where('songs.title', 'like', '%'.$search.'%'))
            ->with(['album', 'artist'])
            ->withFavoriteFlag($user->id)
            ->orderByDesc('favorites.created_at')
            ->orderByDesc('favorites.id')
            ->limit($limit)
            ->get();
    }

    public function songsQuery(User $user): Builder
    {
        return Song::query()
            ->where('songs.user_id', $user->id)
            ->with(['album', 'artist'])
            ->withFavoriteFlag($user->id);
    }
}