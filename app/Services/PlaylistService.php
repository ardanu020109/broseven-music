<?php

namespace App\Services;

use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PlaylistService
{
    public function __construct(private FileUploadService $files)
    {
    }

    public function paginate(User $user, ?string $search = null, int $perPage = 12): LengthAwarePaginator
    {
        return $user->playlists()
            ->search($search)
            ->withCount('songs')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(User $user, array $data, ?UploadedFile $cover = null): Playlist
    {
        $path = $cover ? $this->files->storeImage($cover, "playlists/{$user->id}") : null;

        try {
            return DB::transaction(fn () => $user->playlists()->create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'cover_image' => $path,
            ]));
        } catch (Throwable $e) {
            $this->files->delete($path);

            throw $e;
        }
    }

    public function update(Playlist $playlist, array $data, ?UploadedFile $cover = null, bool $removeCover = false): Playlist
    {
        $oldPath = $playlist->cover_image;
        $newPath = $cover ? $this->files->storeImage($cover, "playlists/{$playlist->user_id}") : null;

        $payload = [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ];

        if ($newPath) {
            $payload['cover_image'] = $newPath;
        } elseif ($removeCover) {
            $payload['cover_image'] = null;
        }

        try {
            DB::transaction(fn () => $playlist->update($payload));
        } catch (Throwable $e) {
            $this->files->delete($newPath);

            throw $e;
        }

        if (($newPath || $removeCover) && $oldPath) {
            $this->files->delete($oldPath);
        }

        return $playlist->refresh();
    }

    public function delete(Playlist $playlist): void
    {
        $path = $playlist->cover_image;

        // Baris playlist_song ikut terhapus oleh cascade; lagu itu sendiri TIDAK terhapus
        DB::transaction(fn () => $playlist->delete());

        $this->files->delete($path);
    }

    /**
     * Lagu dalam playlist, terurut berdasarkan position.
     */
    public function songsFor(Playlist $playlist, User $user): Collection
    {
        return $playlist->songs()
            ->with(['album', 'artist'])
            ->withFavoriteFlag($user->id)
            ->get();
    }

    /**
     * Pilihan lagu milik user yang BELUM ada di playlist (untuk SingleSelect).
     */
    public function addableSongs(Playlist $playlist, User $user): Collection
    {
        return $user->songs()
            ->with('artist:id,name')
            ->whereNotIn('id', DB::table('playlist_song')->where('playlist_id', $playlist->id)->select('song_id'))
            ->orderBy('title')
            ->get(['id', 'title', 'artist_id'])
            ->map(fn (Song $song) => [
                'id'   => $song->id,
                'name' => $song->title.($song->artist ? ' - '.$song->artist->name : ''),
            ])
            ->values();
    }

    public function addSong(Playlist $playlist, Song $song): void
    {
        try {
            DB::transaction(function () use ($playlist, $song) {
                // Kunci baris playlist agar penambahan bersamaan tidak menghasilkan posisi ganda
                Playlist::query()->whereKey($playlist->id)->lockForUpdate()->first();

                if ($playlist->songs()->whereKey($song->id)->exists()) {
                    throw ValidationException::withMessages([
                        'song_id' => 'Lagu sudah ada di playlist ini.',
                    ]);
                }

                $max = (int) DB::table('playlist_song')->where('playlist_id', $playlist->id)->max('position');

                $playlist->songs()->attach($song->id, ['position' => $max + 1]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'song_id' => 'Lagu sudah ada di playlist ini.',
            ]);
        }
    }

    public function removeSong(Playlist $playlist, Song $song): void
    {
        DB::transaction(function () use ($playlist, $song) {
            $removed = $playlist->songs()->detach($song->id);

            abort_if($removed === 0, 404);

            $this->normalizePositions($playlist);
        });
    }

    /**
     * @param  array<int, int|string>  $order  seluruh id lagu playlist dalam urutan baru
     */
    public function reorder(Playlist $playlist, array $order): void
    {
        $given = collect($order)->map(fn ($id) => (int) $id)->values();

        DB::transaction(function () use ($playlist, $given) {
            Playlist::query()->whereKey($playlist->id)->lockForUpdate()->first();

            $current = DB::table('playlist_song')
                ->where('playlist_id', $playlist->id)
                ->pluck('song_id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            if ($given->sort()->values()->all() !== $current) {
                throw ValidationException::withMessages([
                    'order' => 'Urutan tidak valid. Muat ulang halaman lalu coba lagi.',
                ]);
            }

            foreach ($given as $index => $songId) {
                DB::table('playlist_song')
                    ->where('playlist_id', $playlist->id)
                    ->where('song_id', $songId)
                    ->update(['position' => $index + 1, 'updated_at' => now()]);
            }
        });
    }

    private function normalizePositions(Playlist $playlist): void
    {
        $rows = DB::table('playlist_song')
            ->where('playlist_id', $playlist->id)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id');

        foreach ($rows as $index => $rowId) {
            DB::table('playlist_song')->where('id', $rowId)->update(['position' => $index + 1]);
        }
    }
}