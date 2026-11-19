<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddPlaylistSongRequest;
use App\Http\Requests\ReorderPlaylistRequest;
use App\Models\Playlist;
use App\Models\Song;
use App\Services\PlaylistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PlaylistSongController extends Controller
{
    public function __construct(private PlaylistService $playlists)
    {
    }

    public function store(AddPlaylistSongRequest $request, Playlist $playlist): RedirectResponse
    {
        $song = Song::query()->findOrFail($request->validated('song_id'));

        // Pengaman ganda selain aturan exists pada request
        abort_unless((int) $song->user_id === (int) $request->user()->id, 403);

        $this->playlists->addSong($playlist, $song);

        return redirect()->route('playlists.show', $playlist)->with('status', 'Lagu ditambahkan ke playlist.');
    }

    public function destroy(Playlist $playlist, Song $song): RedirectResponse
    {
        Gate::authorize('update', $playlist);

        $this->playlists->removeSong($playlist, $song);

        return redirect()->route('playlists.show', $playlist)->with('status', 'Lagu dikeluarkan dari playlist.');
    }

    public function reorder(ReorderPlaylistRequest $request, Playlist $playlist): RedirectResponse
    {
        $this->playlists->reorder($playlist, $request->validated('order'));

        return redirect()->route('playlists.show', $playlist);
    }
}