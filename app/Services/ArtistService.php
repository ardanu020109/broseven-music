<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class ArtistService
{
    public function __construct(private FileUploadService $files)
    {
    }

    public function paginate(User $user, ?string $search = null, int $perPage = 12): LengthAwarePaginator
    {
        return $user->artists()
            ->search($search)
            ->withCount(['albums', 'songs'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(User $user, array $data, ?UploadedFile $photo = null): Artist
    {
        $path = $photo ? $this->files->storeImage($photo, "artists/{$user->id}") : null;

        try {
            return DB::transaction(fn () => $user->artists()->create([
                'name'      => $data['name'],
                'biography' => $data['biography'] ?? null,
                'photo'     => $path,
            ]));
        } catch (Throwable $e) {
            $this->files->delete($path);

            throw $e;
        }
    }

    public function update(Artist $artist, array $data, ?UploadedFile $photo = null, bool $removePhoto = false): Artist
    {
        $oldPath = $artist->photo;
        $newPath = $photo ? $this->files->storeImage($photo, "artists/{$artist->user_id}") : null;

        $payload = [
            'name'      => $data['name'],
            'biography' => $data['biography'] ?? null,
        ];

        if ($newPath) {
            $payload['photo'] = $newPath;
        } elseif ($removePhoto) {
            $payload['photo'] = null;
        }

        try {
            DB::transaction(fn () => $artist->update($payload));
        } catch (Throwable $e) {
            $this->files->delete($newPath);

            throw $e;
        }

        if (($newPath || $removePhoto) && $oldPath) {
            $this->files->delete($oldPath);
        }

        return $artist->refresh();
    }

    public function delete(Artist $artist): void
    {
        $path = $artist->photo;

        DB::transaction(fn () => $artist->delete());

        $this->files->delete($path);
    }
}