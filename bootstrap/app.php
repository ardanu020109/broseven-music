<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role'   => RoleMiddleware::class,
            'active' => EnsureUserIsActive::class,
        ]);

        // Guest yang membuka halaman terlindungi -> login
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));

        // User yang sudah login membuka halaman guest (mis. /login) -> beranda sesuai role
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->homePath() ?? '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            // Permintaan API/JSON murni dibiarkan apa adanya
            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                return $response;
            }

            $status = $response->getStatusCode();

            $isClientError = in_array($status, [403, 404], true);
            $isServerError = in_array($status, [500, 503], true)
                && ! app()->environment('local', 'testing'); // di local tetap tampil halaman debug

            if ($isClientError || $isServerError) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            if ($status === 419) {
                return back()->with('message', 'Sesi halaman telah kedaluwarsa. Silakan coba lagi.');
            }

            return $response;
        });
    })->create();
