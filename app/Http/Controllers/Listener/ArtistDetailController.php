<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Services\LibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ArtistDetailController extends Controller
{
    public function __invoke(Request $request, Artist $artist, LibraryService $library): Response
    {
        Gate::authorize('view', $artist);

        $user = $request->user();

        return Inertia::render('Listener/Artists/Show', [
            'artist' => $artist,
            'albums' => fn () => $artist->albums()
                ->with('artist:id,name')
                ->withCount('songs')
                ->orderBy('release_year')
                ->orderBy('name')
                ->get(),
            'songs' => fn () => $library->artistSongs($artist, $user),
        ]);
    }
}