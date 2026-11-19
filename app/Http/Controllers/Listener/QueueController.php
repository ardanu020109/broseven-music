<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Services\LibraryService;
use App\Services\PlaylistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Daftar lagu (JSON) untuk antrean pemutar. Setiap endpoint diotorisasi:
 * hanya pemilik koleksi yang boleh mengambil antreannya.
 */
class QueueController extends Controller
{
    public function __construct(
        private LibraryService $library,
        private PlaylistService $playlists,
    ) {
    }

    public function songs(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Song::class);

        return $this->respond($this->library->allSongs($request->user(), [
            'search'    => $this->text($request->query('search')),
            'album_id'  => $this->id($request->query('album_id')),
            'artist_id' => $this->id($request->query('artist_id')),
        ]));
    }

    public function favorites(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Song::class);

        return $this->respond($this->library->favoriteSongs(
            $request->user(),
            $this->text($request->query('search')),
        ));
    }

    public function album(Request $request, Album $album): JsonResponse
    {
        Gate::authorize('view', $album);

        return $this->respond($this->library->albumSongs($album, $request->user()));
    }

    public function artist(Request $request, Artist $artist): JsonResponse
    {
        Gate::authorize('view', $artist);

        return $this->respond($this->library->artistSongs($artist, $request->user()));
    }

    public function playlist(Request $request, Playlist $playlist): JsonResponse
    {
        Gate::authorize('view', $playlist);

        return $this->respond($this->playlists->songsFor($playlist, $request->user()));
    }

    private function respond(iterable $songs): JsonResponse
    {
        return response()->json(['songs' => $songs]);
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = mb_substr(trim($value), 0, 100);

        return $value !== '' ? $value : null;
    }

    private function id(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}