<?php

namespace App\Console\Commands;

use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DbCheckCommand extends Command
{
    protected $signature = 'bs7:db-check';

    protected $description = 'Memeriksa koneksi, tabel, dan relasi model BroSeven! (data uji selalu di-rollback)';

    private bool $allOk = true;

    public function handle(): int
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->error('Koneksi database GAGAL: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Koneksi OK -> database: '.DB::connection()->getDatabaseName());

        foreach (['users', 'sessions', 'artists', 'albums', 'songs', 'playlists', 'playlist_song', 'favorites'] as $table) {
            $this->check("Tabel {$table} ada", Schema::hasTable($table));
        }
        $this->check('Kolom users.role & is_active ada', Schema::hasColumns('users', ['role', 'is_active']));

        $this->check('Superadmin dari seeder ada', User::where('role', User::ROLE_SUPERADMIN)->exists());

        DB::beginTransaction();

        try {
            $user = new User();
            $user->forceFill([
                'name'      => 'Listener Uji',
                'email'     => 'dbcheck+'.Str::lower(Str::random(8)).'@example.test',
                'password'  => 'password-uji-123',
                'role'      => User::ROLE_LISTENER,
                'is_active' => true,
            ])->save();

            $artist = $user->artists()->create(['name' => 'Artis Uji']);
            $album  = $user->albums()->create(['artist_id' => $artist->id, 'name' => 'Album Uji', 'release_year' => 2026]);

            $base = [
                'album_id'  => $album->id,
                'artist_id' => $artist->id,
                'file_size' => 1000,
                'mime_type' => 'audio/mpeg',
            ];
            $songB = $user->songs()->create($base + ['title' => 'Lagu B', 'audio_path' => 'check/b.mp3', 'track_number' => 2]);
            $songA = $user->songs()->create($base + ['title' => 'Lagu A', 'audio_path' => 'check/a.mp3', 'track_number' => 1]);

            $playlist = $user->playlists()->create(['name' => 'Playlist Uji']);
            $playlist->songs()->attach([
                $songB->id => ['position' => 1],
                $songA->id => ['position' => 2],
            ]);

            $user->favorites()->create(['song_id' => $songA->id]);

            $this->check('Password ter-hash (bukan plain text)', $user->password !== 'password-uji-123');
            $this->check('User -> hasMany artists/albums/songs/playlists', $user->artists()->count() === 1
                && $user->albums()->count() === 1
                && $user->songs()->count() === 2
                && $user->playlists()->count() === 1);
            $this->check('Song -> belongsTo user/album/artist', $songA->user->is($user)
                && $songA->album->is($album)
                && $songA->artist->is($artist));
            $this->check('Album->songs terurut track_number (Lagu A dulu)', $album->songs->pluck('title')->all() === ['Lagu A', 'Lagu B']);
            $this->check('Playlist->songs terurut position (Lagu B dulu)', $playlist->songs->pluck('title')->all() === ['Lagu B', 'Lagu A']);
            $this->check('User->favoriteSongs berisi 1 lagu', $user->favoriteSongs()->count() === 1);
            $this->check('audio_path tidak ikut toArray()', ! array_key_exists('audio_path', $songA->toArray()));
            $this->check('role tidak bisa mass-assign', $this->roleIsProtected());

            $this->check('Unique favorite (user_id, song_id) aktif', $this->throwsQueryException(
                fn () => $user->favorites()->create(['song_id' => $songA->id])
            ));
            $this->check('Unique playlist_song (playlist_id, song_id) aktif', $this->throwsQueryException(
                fn () => $playlist->songs()->attach($songA->id, ['position' => 9])
            ));

            // Hapus artis -> artist_id di lagu/album menjadi NULL
            $artist->delete();
            $this->check('Hapus artis -> artist_id lagu jadi NULL', $songA->fresh()->artist_id === null);

            // Hapus user -> seluruh data miliknya ikut terhapus
            $userId = $user->id;
            $user->delete();
            $this->check('Hapus user -> cascade ke songs/albums/playlists/favorites', Song::where('user_id', $userId)->count() === 0
                && DB::table('albums')->where('user_id', $userId)->count() === 0
                && DB::table('playlists')->where('user_id', $userId)->count() === 0
                && DB::table('favorites')->where('user_id', $userId)->count() === 0
                && Artist::where('user_id', $userId)->count() === 0);
        } catch (\Throwable $e) {
            $this->error('Terjadi error saat pengujian relasi: '.$e->getMessage());
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

    private function check(string $label, bool $passed): void
    {
        if ($passed) {
            $this->line("  <fg=green>[OK]</> {$label}");
        } else {
            $this->line("  <fg=red>[X]</>  {$label}");
            $this->allOk = false;
        }
    }

    private function throwsQueryException(callable $callback): bool
    {
        try {
            $callback();
        } catch (QueryException) {
            return true;
        }

        return false;
    }

    private function roleIsProtected(): bool
    {
        $probe = new User(['name' => 'x', 'email' => 'x@example.test', 'role' => User::ROLE_SUPERADMIN]);

        return $probe->role !== User::ROLE_SUPERADMIN;
    }
}