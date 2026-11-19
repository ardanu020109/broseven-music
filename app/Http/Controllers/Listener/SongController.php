<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSongRequest;
use App\Http\Requests\UpdateSongRequest;
use App\Models\Song;
use App\Models\User;
use App\Services\SongService;
use App\Support\AudioFormats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SongController extends Controller
{
    private const FIELDS = ['title', 'album_id', 'artist_id', 'track_number', 'duration'];

    public function __construct(private SongService $songs)
    {
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Song::class);

        $search   = trim((string) $request->query('search', ''));
        $albumId  = $request->query('album_id') ? (int) $request->query('album_id') : null;
        $artistId = $request->query('artist_id') ? (int) $request->query('artist_id') : null;

        return Inertia::render('Listener/Songs/Index', [
            'songs' => $this->songs->paginate($request->user(), [
                'search'    => $search ?: null,
                'album_id'  => $albumId,
                'artist_id' => $artistId,
            ])->withQueryString(),
            'filters'       => ['search' => $search, 'album_id' => $albumId, 'artist_id' => $artistId],
            'albumOptions'  => $this->albumOptions($request->user()),
            'artistOptions' => $this->artistOptions($request->user()),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Song::class);

        return Inertia::render('Listener/Songs/Create', $this->formProps($request->user()));
    }

    public function store(StoreSongRequest $request): RedirectResponse
    {
        $this->songs->create(
            $request->user(),
            $request->safe()->only(self::FIELDS),
            $request->file('audio'),
        );

        return redirect()->route('songs.index')->with('status', 'Lagu berhasil diunggah.');
    }

    public function edit(Request $request, Song $song): Response
    {
        Gate::authorize('update', $song);

        return Inertia::render('Listener/Songs/Edit', [
            'song' => $song,
        ] + $this->formProps($request->user()));
    }

    public function update(UpdateSongRequest $request, Song $song): RedirectResponse
    {
        $this->songs->update(
            $song,
            $request->safe()->only(self::FIELDS),
            $request->file('audio'),
        );

        return redirect()->route('songs.index')->with('status', 'Lagu berhasil diperbarui.');
    }

    public function destroy(Song $song): RedirectResponse
    {
        Gate::authorize('delete', $song);

        $this->songs->delete($song);

        return redirect()->route('songs.index')->with('status', 'Lagu berhasil dihapus.');
    }

    private function formProps(User $user): array
    {
        return [
            'albumOptions'  => $this->albumOptions($user),
            'artistOptions' => $this->artistOptions($user),
            'maxMb'         => (int) floor(config('broseven.audio.max_kb') / 1024),
            'formats'       => AudioFormats::label(),
        ];
    }

    private function albumOptions(User $user)
    {
        return $user->albums()->orderBy('name')->get(['id', 'name', 'artist_id'])->makeHidden(['cover_url']);
    }

    private function artistOptions(User $user)
    {
        return $user->artists()->orderBy('name')->get(['id', 'name'])->makeHidden(['photo_url']);
    }
}