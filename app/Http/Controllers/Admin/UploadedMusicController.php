<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Services\AdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pemantauan musik semua listener. Hanya BACA: tidak ada edit atau hapus.
 */
class UploadedMusicController extends Controller
{
    public function __construct(private AdminService $admin)
    {
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Song::class);

        $search  = $this->text($request->query('search'));
        $ownerId = $this->id($request->query('owner_id'));

        return Inertia::render('Admin/Songs/Index', [
            'songs'        => $this->admin->paginateSongs(['search' => $search, 'owner_id' => $ownerId])->withQueryString(),
            'filters'      => ['search' => $search ?? '', 'owner_id' => $ownerId],
            'ownerOptions' => $this->admin->ownerOptions(),
        ]);
    }

    public function show(Song $song): Response
    {
        Gate::authorize('view', $song);

        $song->load(['user:id,name,email', 'album', 'artist']);

        return Inertia::render('Admin/Songs/Show', [
            'song' => $song,
        ]);
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