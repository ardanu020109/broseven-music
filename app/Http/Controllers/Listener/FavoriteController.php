<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Services\FavoriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    public function __construct(private FavoriteService $favorites)
    {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Song::class);

        $search = trim((string) $request->query('search', ''));

        $songs = $this->favorites->paginate($request->user(), $search ?: null)->withQueryString();

        // Halaman terakhir menjadi kosong setelah lagu dihapus dari favorit
        if ($songs->isEmpty() && $songs->currentPage() > 1) {
            return redirect()->route('favorites.index', array_merge(
                $request->query(),
                ['page' => max(1, $songs->lastPage())],
            ));
        }

        return Inertia::render('Listener/Favorites/Index', [
            'songs'   => $songs,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Toggle favorit. Sengaja tanpa pesan flash agar tidak muncul toast pada tiap klik hati.
     */
    public function toggle(Request $request, Song $song): RedirectResponse
    {
        Gate::authorize('favorite', $song);

        $this->favorites->toggle($request->user(), $song);

        return back();
    }
}