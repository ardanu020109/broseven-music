<?php

namespace App\Services;

use App\Models\Song;
use App\Models\User;
use App\Support\AudioFormats;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class SongService
{
    public function __construct(private FileUploadService $files)
    {
    }

    public function paginate(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $user->songs()
            ->with(['album', 'artist'])
            ->withFavoriteFlag($user->id)
            ->search($filters['search'] ?? null)
            ->when(! empty($filters['album_id']), fn ($q) => $q->where('album_id', $filters['album_id']))
            ->when(! empty($filters['artist_id']), fn ($q) => $q->where('artist_id', $filters['artist_id']))
            ->orderBy('title')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function create(User $user, array $data, UploadedFile $audio): Song
    {
        $extension = strtolower($audio->getClientOriginalExtension());
        $size      = (int) $audio->getSize();
        $path      = $this->files->storeAudio($audio, "songs/{$user->id}", $extension);

        try {
            return DB::transaction(fn () => $user->songs()->create([
                'album_id'     => $data['album_id'] ?? null,
                'artist_id'    => $data['artist_id'] ?? null,
                'title'        => $data['title'],
                'track_number' => $data['track_number'] ?? null,
                'duration'     => $data['duration'] ?? null,
                'audio_path'   => $path,
                'file_size'    => $size,
                'mime_type'    => AudioFormats::contentType($extension),
            ]));
        } catch (Throwable $e) {
            $this->files->delete($path);

            throw $e;
        }
    }

    public function update(Song $song, array $data, ?UploadedFile $audio = null): Song
    {
        $oldPath = $song->audio_path;
        $newPath = null;

        $payload = [
            'album_id'     => $data['album_id'] ?? null,
            'artist_id'    => $data['artist_id'] ?? null,
            'title'        => $data['title'],
            'track_number' => $data['track_number'] ?? null,
        ];

        if ($audio) {
            $extension = strtolower($audio->getClientOriginalExtension());
            $size      = (int) $audio->getSize();
            $newPath   = $this->files->storeAudio($audio, "songs/{$song->user_id}", $extension);

            $payload += [
                'audio_path' => $newPath,
                'file_size'  => $size,
                'mime_type'  => AudioFormats::contentType($extension),
                'duration'   => $data['duration'] ?? null,
            ];
        }

        try {
            DB::transaction(fn () => $song->update($payload));
        } catch (Throwable $e) {
            $this->files->delete($newPath);

            throw $e;
        }

        if ($newPath && $oldPath) {
            $this->files->delete($oldPath);
        }

        return $song->refresh();
    }

    public function delete(Song $song): void
    {
        $path = $song->audio_path;

        // Baris playlist_song dan favorites ikut terhapus oleh cascade database
        DB::transaction(fn () => $song->delete());

        $this->files->delete($path);
    }
}