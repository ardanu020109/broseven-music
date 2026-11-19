<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Services\LibraryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, LibraryService $library): Response
    {
        $user = $request->user();

        $raw = $request->query('search');
        $search = is_string($raw) ? mb_substr(trim($raw), 0, 100) : '';

        return Inertia::render('Listener/Home', [
            'filters'  => ['search' => $search],
            'stats'    => fn () => $library->stats($user),
            'sections' => fn () => $search === '' ? $library->sections($user) : null,
            'results'  => fn () => $search !== '' ? $library->search($user, $search) : null,
        ]);
    }
}