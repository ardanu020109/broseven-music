<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Data ringkasan dan pemantauan musik untuk superadmin (hanya baca).
 */
class AdminService
{
    public const QUEUE_LIMIT = 500;

    public function stats(): array
    {
        $listeners = User::query()->listeners()->count();
        $active    = User::query()->listeners()->where('is_active', true)->count();

        return [
            'listeners'          => $listeners,
            'active_listeners'   => $active,
            'inactive_listeners' => $listeners - $active,
            'songs'              => Song::query()->count(),
            'albums'             => Album::query()->count(),
            'artists'            => Artist::query()->count(),
            'playlists'          => Playlist::query()->count(),
            'storage'            => (int) Song::query()->sum('file_size'),
        ];
    }

    public function recentListeners(int $limit = 5): Collection
    {
        return User::query()
            ->listeners()
            ->withCount('songs')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function recentSongs(int $limit = 8): Collection
    {
        return $this->filteredSongs([])->limit($limit)->get();
    }

    public function paginateSongs(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredSongs($filters)->paginate($perPage);
    }

    public function queueSongs(array $filters = []): Collection
    {
        return $this->filteredSongs($filters)->limit(self::QUEUE_LIMIT)->get();
    }

    public function ownerOptions(): Collection
    {
        return User::query()->listeners()->orderBy('name')->orderBy('id')->get(['id', 'name']);
    }

    /**
     * Semua lagu semua listener, upload terbaru lebih dulu.
     * Pencarian: judul, artis, album, atau nama pemilik. % dan _ diperlakukan sebagai teks biasa.
     */
    public function filteredSongs(array $filters): Builder
    {
        $search  = $filters['search'] ?? null;
        $ownerId = $filters['owner_id'] ?? null;

        return Song::query()
            ->with(['user:id,name', 'album', 'artist'])
            ->when(filled($search), function (Builder $query) use ($search) {
                $like = '%'.addcslashes((string) $search, '%_\\').'%';

                $query->where(function (Builder $w) use ($like) {
                    $w->where('songs.title', 'like', $like)
                        ->orWhereHas('artist', fn ($a) => $a->where('name', 'like', $like))
                        ->orWhereHas('album', fn ($a) => $a->where('name', 'like', $like))
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like));
                });
            })
            ->when($ownerId, fn (Builder $q) => $q->where('songs.user_id', (int) $ownerId))
            ->orderByDesc('songs.created_at')
            ->orderByDesc('songs.id');
    }
}