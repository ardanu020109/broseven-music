<?php

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Melayani gambar privat (foto artis, cover album, cover playlist) dengan otorisasi.
 */
class MediaController extends Controller
{
    public function __construct(private FileUploadService $files)
    {
    }

    public function artistPhoto(Artist $artist): StreamedResponse
    {
        Gate::authorize('photo', $artist);

        return $this->image($artist->photo);
    }

    public function albumCover(Album $album): StreamedResponse
    {
        Gate::authorize('cover', $album);

        return $this->image($album->cover_image);
    }

    public function playlistCover(Playlist $playlist): StreamedResponse
    {
        Gate::authorize('cover', $playlist);

        return $this->image($playlist->cover_image);
    }

    private function image(?string $path): StreamedResponse
    {
        abort_unless($this->files->exists($path), 404);

        return Storage::disk($this->files->disk())->response($path, null, [
            'Cache-Control'          => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}