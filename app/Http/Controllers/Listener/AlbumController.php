<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlbumRequest;
use App\Http\Requests\UpdateAlbumRequest;
use App\Models\Album;
use App\Models\User;
use App\Services\AlbumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AlbumController extends Controller
{
    public function __construct(private AlbumService $albums)
    {
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Album::class);

        $search   = trim((string) $request->query('search', ''));
        $artistId = $request->query('artist_id') ? (int) $request->query('artist_id') : null;

        return Inertia::render('Listener/Albums/Index', [
            'albums'        => $this->albums->paginate($request->user(), $search ?: null, $artistId)->withQueryString(),
            'filters'       => ['search' => $search, 'artist_id' => $artistId],
            'artistOptions' => $this->artistOptions($request->user()),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Album::class);

        return Inertia::render('Listener/Albums/Create', [
            'artistOptions' => $this->artistOptions($request->user()),
        ]);
    }

    public function store(StoreAlbumRequest $request): RedirectResponse
    {
        $this->albums->create(
            $request->user(),
            $request->safe()->only(['name', 'artist_id', 'release_year', 'description']),
            $request->file('cover'),
        );

        return redirect()->route('albums.index')->with('status', 'Album berhasil ditambahkan.');
    }

    public function edit(Request $request, Album $album): Response
    {
        Gate::authorize('update', $album);

        return Inertia::render('Listener/Albums/Edit', [
            'album'         => $album,
            'artistOptions' => $this->artistOptions($request->user()),
        ]);
    }

    public function update(UpdateAlbumRequest $request, Album $album): RedirectResponse
    {
        $this->albums->update(
            $album,
            $request->safe()->only(['name', 'artist_id', 'release_year', 'description']),
            $request->file('cover'),
            $request->boolean('remove_cover'),
        );

        return redirect()->route('albums.index')->with('status', 'Album berhasil diperbarui.');
    }

    public function destroy(Album $album): RedirectResponse
    {
        Gate::authorize('delete', $album);

        $this->albums->delete($album);

        return redirect()->route('albums.index')->with('status', 'Album berhasil dihapus.');
    }

    private function artistOptions(User $user)
    {
        return $user->artists()->orderBy('name')->get(['id', 'name'])->makeHidden(['photo_url']);
    }
}