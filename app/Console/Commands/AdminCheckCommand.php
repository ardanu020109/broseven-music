<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Services\AdminService;
use App\Services\FileUploadService;
use App\Services\ListenerService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminCheckCommand extends Command
{
    protected $signature = 'bs7:admin-check';

    protected $description = 'Memeriksa rute, policy, dan logika superadmin (data uji di-rollback, file uji dibersihkan)';

    private bool $allOk = true;

    public function handle(ListenerService $listeners, AdminService $admin, FileUploadService $files): int
    {
        $this->line('Rute dan middleware');
        foreach ([
            'admin.dashboard', 'admin.listeners.index', 'admin.listeners.create', 'admin.listeners.store',
            'admin.listeners.show', 'admin.listeners.edit', 'admin.listeners.update', 'admin.listeners.destroy',
            'admin.listeners.status', 'admin.songs.index', 'admin.songs.show', 'admin.queue.songs',
            'profile.edit', 'profile.update', 'profile.password',
        ] as $name) {
            $this->check("Rute '{$name}' terdaftar", Route::has($name));
        }

        foreach (['admin.dashboard', 'admin.listeners.index', 'admin.listeners.destroy', 'admin.songs.index', 'admin.queue.songs'] as $name) {
            $mw = Route::getRoutes()->getByName($name)?->gatherMiddleware() ?? [];
            $this->check("'{$name}' dilindungi auth, active, role:superadmin", in_array('auth', $mw, true)
                && in_array('active', $mw, true) && in_array('role:superadmin', $mw, true));
        }

        $mw = Route::getRoutes()->getByName('profile.edit')?->gatherMiddleware() ?? [];
        $this->check("'profile.edit' dilindungi auth dan active", in_array('auth', $mw, true) && in_array('active', $mw, true));

        $this->line('Policy');
        $admin1 = $this->makeUser(1, User::ROLE_SUPERADMIN);
        $admin2 = $this->makeUser(2, User::ROLE_SUPERADMIN);
        $lis1   = $this->makeUser(3, User::ROLE_LISTENER);
        $lis2   = $this->makeUser(4, User::ROLE_LISTENER);
        $song   = (new Song())->forceFill(['user_id' => 3]);

        $this->check('Superadmin boleh viewAny/create user dan view/update/delete listener', Gate::forUser($admin1)->allows('viewAny', User::class)
            && Gate::forUser($admin1)->allows('create', User::class)
            && Gate::forUser($admin1)->allows('view', $lis1)
            && Gate::forUser($admin1)->allows('update', $lis1)
            && Gate::forUser($admin1)->allows('delete', $lis1));
        $this->check('Superadmin TIDAK boleh view/update/delete superadmin lain', Gate::forUser($admin1)->denies('view', $admin2)
            && Gate::forUser($admin1)->denies('update', $admin2)
            && Gate::forUser($admin1)->denies('delete', $admin2));
        $this->check('Listener TIDAK boleh mengelola user', Gate::forUser($lis1)->denies('viewAny', User::class)
            && Gate::forUser($lis1)->denies('create', User::class)
            && Gate::forUser($lis1)->denies('update', $lis2)
            && Gate::forUser($lis1)->denies('delete', $lis2));
        $this->check('Superadmin boleh viewAny/view/play/download lagu', Gate::forUser($admin1)->allows('viewAny', Song::class)
            && Gate::forUser($admin1)->allows('view', $song)
            && Gate::forUser($admin1)->allows('play', $song)
            && Gate::forUser($admin1)->allows('download', $song));
        $this->check('Superadmin TIDAK boleh create/update/delete/favorite/track lagu', Gate::forUser($admin1)->denies('create', Song::class)
            && Gate::forUser($admin1)->denies('update', $song)
            && Gate::forUser($admin1)->denies('delete', $song)
            && Gate::forUser($admin1)->denies('favorite', $song)
            && Gate::forUser($admin1)->denies('track', $song));
        foreach ([Artist::class, Album::class, Playlist::class] as $class) {
            $model = (new $class())->forceFill(['user_id' => 3]);
            $this->check(class_basename($class).': superadmin DITOLAK view/update/delete', Gate::forUser($admin1)->denies('view', $model)
                && Gate::forUser($admin1)->denies('update', $model)
                && Gate::forUser($admin1)->denies('delete', $model));
        }

        $this->line('Logika superadmin (data uji di-rollback)');

        $disk  = Storage::disk($files->disk());
        $ids   = [];
        DB::beginTransaction();

        try {
            $token  = Str::lower(Str::random(8));
            $before = $admin->stats();

            $a = $listeners->create(['name' => "Uji {$token} A", 'email' => "uji{$token}a@example.test", 'password' => 'rahasia-123', 'is_active' => true]);
            $b = $listeners->create(['name' => "Uji {$token} B", 'email' => "uji{$token}b@example.test", 'password' => 'rahasia-123', 'is_active' => false]);
            $ids = [$a->id, $b->id];

            $this->check('create: role listener, status sesuai, password ter-hash', $a->role === User::ROLE_LISTENER
                && $a->is_active === true && $b->is_active === false
                && $a->password !== 'rahasia-123' && Hash::check('rahasia-123', $a->password));
            $this->check('create: email unik ditegakkan database', $this->throwsQuery(
                fn () => $listeners->create(['name' => 'Dobel', 'email' => "uji{$token}a@example.test", 'password' => 'rahasia-123', 'is_active' => true])
            ));

            $audio = "songs/{$a->id}/{$token}.mp3";
            $photo = "artists/{$a->id}/{$token}.jpg";
            $cover = "albums/{$a->id}/{$token}.jpg";
            $plc   = "playlists/{$a->id}/{$token}.jpg";
            foreach ([$audio, $photo, $cover, $plc] as $path) {
                $disk->put($path, 'x');
            }

            $artist   = $a->artists()->create(['name' => "Artis {$token}", 'photo' => $photo]);
            $album    = $a->albums()->create(['artist_id' => $artist->id, 'name' => "Album {$token}", 'cover_image' => $cover]);
            $song     = $a->songs()->create([
                'album_id' => $album->id, 'artist_id' => $artist->id, 'title' => "Lagu {$token}",
                'audio_path' => $audio, 'file_size' => 1234, 'mime_type' => 'audio/mpeg',
            ]);
            $playlist = $a->playlists()->create(['name' => "Playlist {$token}", 'cover_image' => $plc]);
            $playlist->songs()->attach($song->id, ['position' => 1]);
            $a->favorites()->create(['song_id' => $song->id]);

            $after = $admin->stats();
            $this->check('stats: listener +2 (aktif +1, nonaktif +1)', $after['listeners'] === $before['listeners'] + 2
                && $after['active_listeners'] === $before['active_listeners'] + 1
                && $after['inactive_listeners'] === $before['inactive_listeners'] + 1);
            $this->check('stats: lagu, album, artis, playlist masing-masing +1 dan penyimpanan +1234', $after['songs'] === $before['songs'] + 1
                && $after['albums'] === $before['albums'] + 1
                && $after['artists'] === $before['artists'] + 1
                && $after['playlists'] === $before['playlists'] + 1
                && $after['storage'] === $before['storage'] + 1234);

            $this->check('daftar listener: search token -> 2', $listeners->paginate(['search' => $token])->total() === 2);
            $this->check('daftar listener: filter aktif -> 1, nonaktif -> 1', $listeners->paginate(['search' => $token, 'status' => 'active'])->total() === 1
                && $listeners->paginate(['search' => $token, 'status' => 'inactive'])->total() === 1);
            $this->check('daftar listener: search email -> 1', $listeners->paginate(['search' => "uji{$token}a@"])->total() === 1);
            $this->check("daftar listener: search '%' -> 0 (bukan wildcard)", $listeners->paginate(['search' => '%'])->total() === 0);
            $this->check('daftar listener: hanya berperan listener', $listeners->paginate([])->getCollection()->every(fn ($u) => $u->role === User::ROLE_LISTENER));

            $row = $listeners->paginate(['search' => "uji{$token}a@"])->first();
            $this->check('daftar listener: jumlah lagu/album/artis/playlist dan ukuran benar', $row->songs_count === 1
                && $row->albums_count === 1 && $row->artists_count === 1 && $row->playlists_count === 1
                && (int) $row->songs_size === 1234);
            $this->check('detail listener: stats lengkap', $listeners->stats($a) === [
                'songs' => 1, 'albums' => 1, 'artists' => 1, 'playlists' => 1, 'favorites' => 1, 'storage' => 1234,
            ]);

            $this->check('musik admin: search judul -> 1', $admin->paginateSongs(['search' => $token])->total() === 1);
            $this->check('musik admin: search nama pemilik -> 1', $admin->paginateSongs(['search' => "Uji {$token} A"])->total() === 1);
            $this->check('musik admin: filter pemilik -> 1, pemilik lain -> 0', $admin->paginateSongs(['owner_id' => $a->id])->total() === 1
                && $admin->paginateSongs(['owner_id' => $b->id])->total() === 0);
            $this->check("musik admin: search '%' -> 0 (bukan wildcard)", $admin->paginateSongs(['search' => '%'])->total() === 0);
            $first = $admin->paginateSongs(['owner_id' => $a->id])->first();
            $this->check('musik admin: relasi pemilik, album, artis dimuat', $first->relationLoaded('user') && $first->relationLoaded('album') && $first->relationLoaded('artist'));
            $queue = $admin->queueSongs(['owner_id' => $a->id]);
            $arr = $queue->first()?->toArray() ?? [];
            $this->check('antrean admin: 1 lagu, ada stream_url dan pemilik, tanpa audio_path', $queue->count() === 1
                && isset($arr['stream_url'], $arr['user']) && ! array_key_exists('audio_path', $arr));
            $this->check('Upload terbaru: lagu uji ada di daftar teratas', $admin->recentSongs(5)->pluck('id')->contains($song->id));
            $this->check('Listener terbaru: berisi listener uji', $admin->recentListeners(5)->pluck('id')->intersect($ids)->count() === 2);

            // Update
            $oldHash = $a->password;
            $listeners->update($a, ['name' => "Uji {$token} A2", 'email' => "uji{$token}a2@example.test", 'password' => null, 'is_active' => true]);
            $this->check('update tanpa password: hash tidak berubah, nama dan email berubah', $a->password === $oldHash
                && $a->name === "Uji {$token} A2" && $a->email === "uji{$token}a2@example.test");

            $this->insertSession($a->id);
            $listeners->update($a, ['name' => $a->name, 'email' => $a->email, 'password' => 'baru-12345', 'is_active' => true]);
            $this->check('update dengan password: hash baru dan remember token diganti', Hash::check('baru-12345', $a->password) && filled($a->remember_token));
            $this->check('update dengan password: sesi login dicabut', DB::table('sessions')->where('user_id', $a->id)->count() === 0);

            $this->insertSession($a->id);
            $listeners->setActive($a, false);
            $this->check('nonaktifkan: status false dan sesi dicabut', $a->fresh()->is_active === false
                && DB::table('sessions')->where('user_id', $a->id)->count() === 0);
            $listeners->setActive($a, true);
            $this->check('aktifkan: status true', $a->fresh()->is_active === true);

            // Hapus
            $this->insertSession($a->id);
            $listeners->delete($a);
            $this->check('hapus: baris user, lagu, album, artis, playlist, favorit, pivot, sesi hilang', User::query()->whereKey($a->id)->doesntExist()
                && DB::table('songs')->where('user_id', $a->id)->doesntExist()
                && DB::table('albums')->where('user_id', $a->id)->doesntExist()
                && DB::table('artists')->where('user_id', $a->id)->doesntExist()
                && DB::table('playlists')->where('user_id', $a->id)->doesntExist()
                && DB::table('favorites')->where('user_id', $a->id)->doesntExist()
                && DB::table('playlist_song')->where('song_id', $song->id)->doesntExist()
                && DB::table('sessions')->where('user_id', $a->id)->doesntExist());
            $this->check('hapus: audio, foto artis, cover album, dan cover playlist terhapus', ! $disk->exists($audio)
                && ! $disk->exists($photo) && ! $disk->exists($cover) && ! $disk->exists($plc));
            $this->check('hapus: folder milik listener terhapus', ! $disk->exists("songs/{$a->id}")
                && ! $disk->exists("artists/{$a->id}") && ! $disk->exists("albums/{$a->id}") && ! $disk->exists("playlists/{$a->id}"));
            $this->check('hapus: listener lain dan superadmin tidak terpengaruh', User::query()->whereKey($b->id)->exists()
                && User::query()->where('role', User::ROLE_SUPERADMIN)->exists());
        } catch (\Throwable $e) {
            $this->error('Terjadi error saat pengujian: '.$e->getMessage());
            $this->allOk = false;
        } finally {
            DB::rollBack();

            foreach ($ids as $id) {
                foreach (['songs', 'artists', 'albums', 'playlists'] as $directory) {
                    $disk->deleteDirectory("{$directory}/{$id}");
                }
            }

            $this->line('<fg=gray>Seluruh data uji di-rollback dan file uji dibersihkan.</>');
        }

        if ($this->allOk) {
            $this->info('SEMUA PENGECEKAN LULUS');

            return self::SUCCESS;
        }

        $this->error('ADA PENGECEKAN YANG GAGAL (lihat tanda X di atas)');

        return self::FAILURE;
    }

    private function insertSession(int $userId): void
    {
        DB::table('sessions')->insert([
            'id'            => Str::random(40),
            'user_id'       => $userId,
            'payload'       => 'x',
            'last_activity' => time(),
        ]);
    }

    private function makeUser(int $id, string $role): User
    {
        return (new User())->forceFill(['id' => $id, 'role' => $role, 'is_active' => true]);
    }

    private function throwsQuery(callable $callback): bool
    {
        try {
            $callback();
        } catch (QueryException) {
            return true;
        }

        return false;
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