<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlaylistRequest;
use App\Http\Requests\UpdatePlaylistRequest;
use App\Models\Playlist;
use App\Services\PlaylistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlaylistController extends Controller
{
    public function __construct(private PlaylistService $playlists)
    {
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Playlist::class);

        $search = trim((string) $request->query('search', ''));

        return Inertia::render('Listener/Playlists/Index', [
            'playlists' => $this->playlists->paginate($request->user(), $search ?: null)->withQueryString(),
            'filters'   => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Playlist::class);

        return Inertia::render('Listener/Playlists/Create');
    }

    public function store(StorePlaylistRequest $request): RedirectResponse
    {
        $playlist = $this->playlists->create(
            $request->user(),
            $request->safe()->only(['name', 'description']),
            $request->file('cover'),
        );

        return redirect()->route('playlists.show', $playlist)->with('status', 'Playlist berhasil dibuat.');
    }

    public function show(Request $request, Playlist $playlist): Response
    {
        Gate::authorize('view', $playlist);

        $user = $request->user();

        // Closure: hanya dievaluasi bila diminta (mendukung partial reload)
        return Inertia::render('Listener/Playlists/Show', [
            'playlist'   => $playlist,
            'songs'      => fn () => $this->playlists->songsFor($playlist, $user),
            'addOptions' => fn () => $this->playlists->addableSongs($playlist, $user),
        ]);
    }

    public function edit(Playlist $playlist): Response
    {
        Gate::authorize('update', $playlist);

        return Inertia::render('Listener/Playlists/Edit', [
            'playlist' => $playlist,
        ]);
    }

    public function update(UpdatePlaylistRequest $request, Playlist $playlist): RedirectResponse
    {
        $this->playlists->update(
            $playlist,
            $request->safe()->only(['name', 'description']),
            $request->file('cover'),
            $request->boolean('remove_cover'),
        );

        return redirect()->route('playlists.show', $playlist)->with('status', 'Playlist berhasil diperbarui.');
    }

    public function destroy(Playlist $playlist): RedirectResponse
    {
        Gate::authorize('delete', $playlist);

        $this->playlists->delete($playlist);

        return redirect()->route('playlists.index')->with('status', 'Playlist berhasil dihapus.');
    }
}