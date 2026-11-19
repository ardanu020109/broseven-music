<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Daftar lagu (JSON) untuk antrean pemutar superadmin.
 */
class MusicQueueController extends Controller
{
    public function __invoke(Request $request, AdminService $admin): JsonResponse
    {
        Gate::authorize('viewAny', Song::class);

        $search = $request->query('search');
        $search = is_string($search) ? mb_substr(trim($search), 0, 100) : null;
        $owner  = $request->query('owner_id');

        return response()->json([
            'songs' => $admin->queueSongs([
                'search'   => $search !== '' ? $search : null,
                'owner_id' => is_numeric($owner) && (int) $owner > 0 ? (int) $owner : null,
            ]),
        ]);
    }
}