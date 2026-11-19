<?php

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Services\MusicStreamService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Streaming dan download audio privat. Otorisasi lewat SongPolicy:
 * listener pemilik atau superadmin.
 */
class MusicController extends Controller
{
    public function __construct(private MusicStreamService $music)
    {
    }

    public function stream(Song $song): BinaryFileResponse
    {
        Gate::authorize('play', $song);

        return $this->music->stream($song);
    }

    public function download(Song $song): BinaryFileResponse
    {
        Gate::authorize('download', $song);

        return $this->music->download($song);
    }
}