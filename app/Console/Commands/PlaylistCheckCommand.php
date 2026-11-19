<?php

namespace App\Console\Commands;

use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Services\FavoriteService;
use App\Services\PlaylistService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PlaylistCheckCommand extends Command
{
    protected $signature = 'bs7:playlist-check';

    protected $description = 'Memeriksa rute, policy, dan logika playlist/favorit (data uji di-rollback)';

    private bool $allOk = true;

    public function handle(PlaylistService $playlists, FavoriteService $favorites): int
    {
        $this->line('Rute');
        foreach ([
            'playlists.index', 'playlists.create', 'playlists.store', 'playlists.show', 'playlists.edit',
            'playlists.update', 'playlists.destroy', 'playlists.songs.store', 'playlists.songs.destroy',
            'playlists.songs.reorder', 'songs.favorite', 'favorites.index', 'media.playlist.cover',
        ] as $name) {
            $this->check("Rute '{$name}' terdaftar", Route::has($name));
        }

        $this->line('Policy');
        $owner = $this->makeUser(1, User::ROLE_LISTENER);
        $other = $this->makeUser(2, User::ROLE_LISTENER);
        $admin = $this->makeUser(3, User::ROLE_SUPERADMIN);
        $playlist = (new Playlist())->forceFill(['user_id' => 1]);
        $song = (new Song())->forceFill(['user_id' => 1]);

        $this->check('Playlist: pemilik boleh view/update/delete', Gate::forUser($owner)->allows('view', $playlist)
            && Gate::forUser($owner)->allows('update', $playlist)
            && Gate::forUser($owner)->allows('delete', $playlist));
        $this->check('Playlist: listener lain DITOLAK', Gate::forUser($other)->denies('view', $playlist)
            && Gate::forUser($other)->denies('update', $playlist)
            && Gate::forUser($other)->denies('delete', $playlist));
        $this->check('Playlist: superadmin DITOLAK view/update/delete', Gate::forUser($admin)->denies('view', $playlist)
            && Gate::forUser($admin)->denies('update', $playlist)
            && Gate::forUser($admin)->denies('delete', $playlist));
        $this->check('Cover playlist: pemilik dan superadmin boleh, lainnya DITOLAK', Gate::forUser($owner)->allows('cover', $playlist)
            && Gate::forUser($admin)->allows('cover', $playlist)
            && Gate::forUser($other)->denies('cover', $playlist));
        $this->check('Favorit: hanya pemilik lagu', Gate::forUser($owner)->allows('favorite', $song)
            && Gate::forUser($other)->denies('favorite', $song)
            && Gate::forUser($admin)->denies('favorite', $song));

        $this->line('Logika playlist dan favorit (data uji di-rollback)');
        DB::beginTransaction();

        try {
            $user = $this->createUser('Uji Playlist');
            $mk = fn (string $title) => $user->songs()->create([
                'title'      => $title,
                'audio_path' => 'check/'.Str::random(6).'.mp3',
                'file_size'  => 1,
                'mime_type'  => 'audio/mpeg',
            ]);

            $a = $mk('Lagu A');
            $b = $mk('Lagu B');
            $c = $mk('Lagu C');
            $pl = $user->playlists()->create(['name' => 'Playlist Uji']);

            $order = fn () => DB::table('playlist_song')->where('playlist_id', $pl->id)->orderBy('position')
                ->pluck('song_id')->map(fn ($v) => (int) $v)->all();
            $pos = fn () => DB::table('playlist_song')->where('playlist_id', $pl->id)->orderBy('position')
                ->pluck('position')->map(fn ($v) => (int) $v)->all();

            foreach ([$a, $b, $c] as $s) {
                $playlists->addSong($pl, $s);
            }
            $this->check('Posisi awal 1,2,3 sesuai urutan tambah', $order() === [$a->id, $b->id, $c->id] && $pos() === [1, 2, 3]);
            $this->check('Lagu yang sama TIDAK bisa ditambah dua kali', $this->throws(fn () => $playlists->addSong($pl, $a), ValidationException::class));
            $this->check('Jumlah lagu tetap 3 setelah percobaan duplikat', count($order()) === 3);

            $playlists->reorder($pl, [$c->id, $a->id, $b->id]);
            $this->check('Reorder: urutan C,A,B dengan posisi 1,2,3', $order() === [$c->id, $a->id, $b->id] && $pos() === [1, 2, 3]);
            $this->check('Reorder tidak lengkap DITOLAK', $this->throws(fn () => $playlists->reorder($pl, [$c->id, $a->id]), ValidationException::class));
            $this->check('Reorder dengan id asing DITOLAK', $this->throws(fn () => $playlists->reorder($pl, [$c->id, $a->id, $b->id, 999999999]), ValidationException::class));
            $this->check('Reorder dengan id ganda DITOLAK', $this->throws(fn () => $playlists->reorder($pl, [$c->id, $c->id, $a->id]), ValidationException::class));
            $this->check('Urutan tidak berubah setelah reorder gagal', $order() === [$c->id, $a->id, $b->id]);

            $playlists->removeSong($pl, $c);
            $this->check('Keluarkan lagu: sisa A,B dengan posisi dirapikan 1,2', $order() === [$a->id, $b->id] && $pos() === [1, 2]);
            $this->check('Lagu yang dikeluarkan tetap ada di koleksi', Song::query()->whereKey($c->id)->exists());
            $this->check('Mengeluarkan lagu yang bukan anggota -> 404', $this->throws(fn () => $playlists->removeSong($pl, $c), HttpException::class));

            $addable = $playlists->addableSongs($pl, $user)->pluck('id')->all();
            $this->check('addableSongs hanya berisi lagu yang belum ada di playlist', $addable === [$c->id]);

            $this->check('Favorit toggle pertama -> true', $favorites->toggle($user, $a) === true);
            $this->check('Favorit toggle kedua -> false', $favorites->toggle($user, $a) === false);
            $favorites->add($user, $a);
            $favorites->add($user, $a);
            $this->check('Tambah favorit dua kali tetap 1 baris', $user->favorites()->count() === 1);
            $this->check('Unique (user_id, song_id) di database aktif', $this->throwsQuery(
                fn () => DB::table('favorites')->insert([
                    'user_id' => $user->id, 'song_id' => $a->id, 'created_at' => now(), 'updated_at' => now(),
                ])
            ));

            $rows = $user->songs()->withFavoriteFlag($user->id)->get();
            $this->check('Flag is_favorite benar (A true, B false)', (bool) $rows->firstWhere('id', $a->id)->is_favorite === true
                && (bool) $rows->firstWhere('id', $b->id)->is_favorite === false);

            $page = $favorites->paginate($user);
            $this->check('Daftar favorit: total 1 dan judulnya Lagu A', $page->total() === 1 && $page->first()->title === 'Lagu A');

            $favorites->remove($user, $a);
            $favorites->remove($user, $a);
            $this->check('Hapus favorit (dua kali) aman dan menyisakan 0', $user->favorites()->count() === 0);

            $favorites->add($user, $a);
            $a->delete();
            $this->check('Hapus lagu: cascade ke playlist_song dan favorites', DB::table('playlist_song')->where('song_id', $a->id)->count() === 0
                && DB::table('favorites')->where('song_id', $a->id)->count() === 0);

            $pl->delete();
            $this->check('Hapus playlist: baris pivot hilang, lagu tetap ada', DB::table('playlist_song')->where('playlist_id', $pl->id)->count() === 0
                && Song::query()->whereKey($b->id)->exists());
        } catch (\Throwable $e) {
            $this->error('Terjadi error saat pengujian: '.$e->getMessage());
            $this->allOk = false;
        } finally {
            DB::rollBack();
            $this->line('<fg=gray>Seluruh data uji sudah di-rollback (database tidak berubah).</>');
        }

        if ($this->allOk) {
            $this->info('SEMUA PENGECEKAN LULUS');

            return self::SUCCESS;
        }

        $this->error('ADA PENGECEKAN YANG GAGAL (lihat tanda X di atas)');

        return self::FAILURE;
    }

    private function createUser(string $name): User
    {
        $user = new User();
        $user->forceFill([
            'name'      => $name,
            'email'     => 'plcheck+'.Str::lower(Str::random(8)).'@example.test',
            'password'  => 'password-uji-123',
            'role'      => User::ROLE_LISTENER,
            'is_active' => true,
        ])->save();

        return $user;
    }

    private function makeUser(int $id, string $role): User
    {
        return (new User())->forceFill(['id' => $id, 'role' => $role, 'is_active' => true]);
    }

    private function throws(callable $callback, string $class): bool
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            return $e instanceof $class;
        }

        return false;
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