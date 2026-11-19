<?php

namespace App\Services;

use App\Models\Album;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class AlbumService
{
    public function __construct(private FileUploadService $files)
    {
    }

    public function paginate(User $user, ?string $search = null, ?int $artistId = null, int $perPage = 12): LengthAwarePaginator
    {
        return $user->albums()
            ->with('artist:id,name')
            ->withCount('songs')
            ->search($search)
            ->when($artistId, fn ($query) => $query->where('artist_id', $artistId))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(User $user, array $data, ?UploadedFile $cover = null): Album
    {
        $path = $cover ? $this->files->storeImage($cover, "albums/{$user->id}") : null;

        try {
            return DB::transaction(fn () => $user->albums()->create([
                'artist_id'    => $data['artist_id'] ?? null,
                'name'         => $data['name'],
                'release_year' => $data['release_year'] ?? null,
                'description'  => $data['description'] ?? null,
                'cover_image'  => $path,
            ]));
        } catch (Throwable $e) {
            $this->files->delete($path);

            throw $e;
        }
    }

    public function update(Album $album, array $data, ?UploadedFile $cover = null, bool $removeCover = false): Album
    {
        $oldPath = $album->cover_image;
        $newPath = $cover ? $this->files->storeImage($cover, "albums/{$album->user_id}") : null;

        $payload = [
            'artist_id'    => $data['artist_id'] ?? null,
            'name'         => $data['name'],
            'release_year' => $data['release_year'] ?? null,
            'description'  => $data['description'] ?? null,
        ];

        if ($newPath) {
            $payload['cover_image'] = $newPath;
        } elseif ($removeCover) {
            $payload['cover_image'] = null;
        }

        try {
            DB::transaction(fn () => $album->update($payload));
        } catch (Throwable $e) {
            $this->files->delete($newPath);

            throw $e;
        }

        if (($newPath || $removeCover) && $oldPath) {
            $this->files->delete($oldPath);
        }

        return $album->refresh();
    }

    public function delete(Album $album): void
    {
        $path = $album->cover_image;

        DB::transaction(fn () => $album->delete());

        $this->files->delete($path);
    }
}