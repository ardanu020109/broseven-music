<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class AuthCheckCommand extends Command
{
    protected $signature = 'bs7:auth-check';

    protected $description = 'Memeriksa rute, alias middleware, dan matriks policy (model dibuat di memori, tanpa database)';

    private bool $allOk = true;

    public function handle(): int
    {
        app(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();
        $this->line('Rute dan middleware');
        foreach (['login', 'login.store', 'logout', 'home', 'admin.dashboard'] as $name) {
            $this->check("Rute '{$name}' terdaftar", Route::has($name));
        }
        $router = app('router');
        $aliases = $router->getMiddleware();

        $roleRegistered = isset($aliases['role'])
            && $aliases['role'] === \App\Http\Middleware\RoleMiddleware::class;

        $activeRegistered = isset($aliases['active'])
            && $aliases['active'] === \App\Http\Middleware\EnsureUserIsActive::class;

        $this->check(
            "Alias middleware 'role' dan 'active' terdaftar",
            $roleRegistered && $activeRegistered
        );

        $listenerA = $this->makeUser(1, User::ROLE_LISTENER);
        $listenerB = $this->makeUser(2, User::ROLE_LISTENER);
        $admin     = $this->makeUser(3, User::ROLE_SUPERADMIN);
        $admin2    = $this->makeUser(4, User::ROLE_SUPERADMIN);

        $this->check('homePath: superadmin -> /admin/dashboard', $admin->homePath() === '/admin/dashboard');
        $this->check('homePath: listener -> /home', $listenerA->homePath() === '/home');

        $song = (new Song())->forceFill(['user_id' => 1]);

        $this->line('Policy lagu');
        foreach (['view', 'play', 'download', 'update', 'delete', 'favorite'] as $ability) {
            $this->check("Listener pemilik boleh {$ability}", $this->can($listenerA, $ability, $song));
            $this->check("Listener lain TIDAK boleh {$ability}", ! $this->can($listenerB, $ability, $song));
        }
        foreach (['view', 'play', 'download'] as $ability) {
            $this->check("Superadmin boleh {$ability}", $this->can($admin, $ability, $song));
        }
        foreach (['update', 'delete', 'favorite'] as $ability) {
            $this->check("Superadmin TIDAK boleh {$ability}", ! $this->can($admin, $ability, $song));
        }
        $this->check('Listener boleh create lagu', $this->can($listenerA, 'create', Song::class));
        $this->check('Superadmin TIDAK boleh create lagu', ! $this->can($admin, 'create', Song::class));
        $this->check('Superadmin boleh viewAny lagu (monitoring)', $this->can($admin, 'viewAny', Song::class));

        $this->line('Policy artis, album, playlist');
        foreach ([Artist::class, Album::class, Playlist::class] as $class) {
            $model = (new $class())->forceFill(['user_id' => 1]);
            $short = class_basename($class);
            $this->check("{$short}: pemilik boleh view/update/delete", $this->can($listenerA, 'view', $model)
                && $this->can($listenerA, 'update', $model)
                && $this->can($listenerA, 'delete', $model));
            $this->check("{$short}: listener lain ditolak", ! $this->can($listenerB, 'view', $model)
                && ! $this->can($listenerB, 'update', $model)
                && ! $this->can($listenerB, 'delete', $model));
            $this->check("{$short}: superadmin ditolak", ! $this->can($admin, 'view', $model)
                && ! $this->can($admin, 'update', $model)
                && ! $this->can($admin, 'delete', $model));
        }

        $this->line('Policy manajemen listener');
        $this->check('Superadmin boleh viewAny/create user', $this->can($admin, 'viewAny', User::class)
            && $this->can($admin, 'create', User::class));
        $this->check('Superadmin boleh update/delete listener', $this->can($admin, 'update', $listenerA)
            && $this->can($admin, 'delete', $listenerA));
        $this->check('Superadmin TIDAK boleh update/delete superadmin lain', ! $this->can($admin, 'update', $admin2)
            && ! $this->can($admin, 'delete', $admin2));
        $this->check('Listener TIDAK boleh mengelola user', ! $this->can($listenerA, 'viewAny', User::class)
            && ! $this->can($listenerA, 'update', $listenerB));

        if ($this->allOk) {
            $this->info('SEMUA PENGECEKAN LULUS');

            return self::SUCCESS;
        }

        $this->error('ADA PENGECEKAN YANG GAGAL (lihat tanda X di atas)');

        return self::FAILURE;
    }

    private function makeUser(int $id, string $role): User
    {
        return (new User())->forceFill(['id' => $id, 'role' => $role, 'is_active' => true]);
    }

    private function can(User $user, string $ability, mixed $target): bool
    {
        return Gate::forUser($user)->allows($ability, $target);
    }

    private function check(string $label, bool $passed): void
    {
        if ($passed) {
            $this->line("  <fg=green>[OK]</> {$label}");
        } else {
            $this->line("  <fg=red>[X]</>  {$label}");
            $this->allOk = false;
        }
    }
}
