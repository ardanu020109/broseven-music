<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(AdminService $admin): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats'           => $admin->stats(),
            'recentListeners' => $admin->recentListeners(),
            'recentSongs'     => $admin->recentSongs(),
        ]);
    }
}