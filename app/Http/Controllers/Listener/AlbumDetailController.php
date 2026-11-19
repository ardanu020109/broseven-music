<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\LibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AlbumDetailController extends Controller
{
    public function __invoke(Request $request, Album $album, LibraryService $library): Response
    {
        Gate::authorize('view', $album);

        $user = $request->user();
        $album->load('artist:id,name');

        return Inertia::render('Listener/Albums/Show', [
            'album' => $album,
            'songs' => fn () => $library->albumSongs($album, $user),
        ]);
    }
}