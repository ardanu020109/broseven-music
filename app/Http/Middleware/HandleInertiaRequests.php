<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Data yang dibagikan ke semua halaman Vue.
     * Hanya field aman yang dikirim (tanpa password/token).
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'status'  => fn () => $request->session()->get('status'),
            ],
        ];
    }
}