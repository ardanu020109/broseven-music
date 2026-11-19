<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Mencatat waktu terakhir lagu diputar (untuk Recently Played).
 * Memakai query langsung agar updated_at lagu tidak ikut berubah.
 */
class SongPlayController extends Controller
{
    public function __invoke(Request $request, Song $song): Response
    {
        Gate::authorize('track', $song);

        DB::table('songs')->where('id', $song->id)->update(['last_played_at' => now()]);

        return response()->noContent();
    }
}