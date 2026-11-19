<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArtistRequest;
use App\Http\Requests\UpdateArtistRequest;
use App\Models\Artist;
use App\Services\ArtistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ArtistController extends Controller
{
    public function __construct(private ArtistService $artists)
    {
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Artist::class);

        $search = trim((string) $request->query('search', ''));

        return Inertia::render('Listener/Artists/Index', [
            'artists' => $this->artists->paginate($request->user(), $search ?: null)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Artist::class);

        return Inertia::render('Listener/Artists/Create');
    }

    public function store(StoreArtistRequest $request): RedirectResponse
    {
        $this->artists->create(
            $request->user(),
            $request->safe()->only(['name', 'biography']),
            $request->file('photo'),
        );

        return redirect()->route('artists.index')->with('status', 'Artis berhasil ditambahkan.');
    }

    public function edit(Artist $artist): Response
    {
        Gate::authorize('update', $artist);

        return Inertia::render('Listener/Artists/Edit', [
            'artist' => $artist,
        ]);
    }

    public function update(UpdateArtistRequest $request, Artist $artist): RedirectResponse
    {
        $this->artists->update(
            $artist,
            $request->safe()->only(['name', 'biography']),
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        return redirect()->route('artists.index')->with('status', 'Artis berhasil diperbarui.');
    }

    public function destroy(Artist $artist): RedirectResponse
    {
        Gate::authorize('delete', $artist);

        $this->artists->delete($artist);

        return redirect()->route('artists.index')->with('status', 'Artis berhasil dihapus.');
    }
}