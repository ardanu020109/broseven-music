<?php

namespace App\Services;

use App\Models\Song;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;

class FavoriteService
{
    public function isFavorite(User $user, Song $song): bool
    {
        return $user->favorites()->where('song_id', $song->id)->exists();
    }

    public function add(User $user, Song $song): void
    {
        try {
            $user->favorites()->firstOrCreate(['song_id' => $song->id]);
        } catch (UniqueConstraintViolationException) {
            // Sudah ada (permintaan bersamaan); tidak perlu tindakan
        }
    }

    public function remove(User $user, Song $song): void
    {
        $user->favorites()->where('song_id', $song->id)->delete();
    }

    /**
     * @return bool status favorit setelah toggle
     */
    public function toggle(User $user, Song $song): bool
    {
        if ($this->isFavorite($user, $song)) {
            $this->remove($user, $song);

            return false;
        }

        $this->add($user, $song);

        return true;
    }

    public function paginate(User $user, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Song::query()
            ->select('songs.*')
            ->join('favorites', 'favorites.song_id', '=', 'songs.id')
            ->where('favorites.user_id', $user->id)
            ->where('songs.user_id', $user->id)
            ->when(filled($search), fn ($q) => $q->where('songs.title', 'like', '%'.$search.'%'))
            ->with(['album', 'artist'])
            ->withFavoriteFlag($user->id)
            ->orderByDesc('favorites.created_at')
            ->orderByDesc('favorites.id')
            ->paginate($perPage);
    }
}