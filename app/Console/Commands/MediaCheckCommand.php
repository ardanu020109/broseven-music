<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaCheckCommand extends Command
{
    protected $signature = 'bs7:media-check';

    protected $description = 'Memeriksa rute, storage privat, dan policy media Phase 4';

    private bool $allOk = true;

    public function handle(FileUploadService $files): int
    {
        $this->line('Rute');
        $routes = [
            'artists.index', 'artists.create', 'artists.store', 'artists.edit', 'artists.update', 'artists.destroy',
            'albums.index', 'albums.create', 'albums.store', 'albums.edit', 'albums.update', 'albums.destroy',
            'media.artist.photo', 'media.album.cover',
        ];
        foreach ($routes as $name) {
            $this->check("Rute '{$name}' terdaftar", Route::has($name));
        }

        $this->line('Storage');
        $diskName = $files->disk();
        $root = str_replace('\\', '/', (string) config("filesystems.disks.{$diskName}.root"));
        $this->check(
            "Disk '{$diskName}' privat (bukan storage/app/public)",
            ! str_contains($root, 'storage/app/public') && config("filesystems.disks.{$diskName}.visibility") !== 'public'
        );

        $path = 'bs7-check/'.Str::random(10).'.txt';
        try {
            Storage::disk($diskName)->put($path, 'ok');
            $this->check('Tulis file ke disk', Storage::disk($diskName)->exists($path));
            $files->delete($path);
            $this->check('Hapus file lewat FileUploadService', ! Storage::disk($diskName)->exists($path));
            Storage::disk($diskName)->deleteDirectory('bs7-check');
        } catch (\Throwable $e) {
            $this->check('Disk dapat ditulis ('.$e->getMessage().')', false);
        }

        $this->line('Policy media');
        $owner = $this->makeUser(1, User::ROLE_LISTENER);
        $other = $this->makeUser(2, User::ROLE_LISTENER);
        $admin = $this->makeUser(3, User::ROLE_SUPERADMIN);

        $artist = (new Artist())->forceFill(['user_id' => 1]);
        $album  = (new Album())->forceFill(['user_id' => 1]);

        $this->check('Foto artis: pemilik boleh', Gate::forUser($owner)->allows('photo', $artist));
        $this->check('Foto artis: superadmin boleh', Gate::forUser($admin)->allows('photo', $artist));
        $this->check('Foto artis: listener lain DITOLAK', Gate::forUser($other)->denies('photo', $artist));
        $this->check('Cover album: pemilik boleh', Gate::forUser($owner)->allows('cover', $album));
        $this->check('Cover album: superadmin boleh', Gate::forUser($admin)->allows('cover', $album));
        $this->check('Cover album: listener lain DITOLAK', Gate::forUser($other)->denies('cover', $album));
        $this->check('Superadmin TIDAK boleh edit/hapus album', Gate::forUser($admin)->denies('update', $album)
            && Gate::forUser($admin)->denies('delete', $album));

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