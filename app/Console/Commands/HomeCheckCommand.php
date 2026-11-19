<?php

namespace App\Console\Commands;

use App\Models\Song;
use App\Models\User;
use App\Services\LibraryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HomeCheckCommand extends Command
{
    protected $signature = 'bs7:home-check';

    protected $description = 'Memeriksa rute, skema, policy, pencarian, dan antrean Beranda (data uji di-rollback)';

    private bool $allOk = true;

    public function handle(LibraryService $library): int
    {
        $this->line('Rute dan skema');
        foreach ([
            'home', 'albums.show', 'artists.show', 'songs.played',
            'queue.songs', 'queue.favorites', 'queue.albums', 'queue.artists', 'queue.playlists',
        ] as $name) {
            $this->check("Rute '{$name}' terdaftar", Route::has($name));
        }
        $this->check('Kolom songs.last_played_at ada (jalankan php artisan migrate jika [X])', Schema::hasColumn('songs', 'last_played_at'));

        if (! Schema::hasColumn('songs', 'last_played_at')) {
            $this->error('Migration belum dijalankan. Jalankan: php artisan migrate');

            return self::FAILURE;
        }

        $this->line('Policy');
        $owner = $this->makeUser(1, User::ROLE_LISTENER);
        $other = $this->makeUser(2, User::ROLE_LISTENER);
        $admin = $this->makeUser(3, User::ROLE_SUPERADMIN);
        $song = (new Song())->forceFill(['user_id' => 1]);
        $this->check('track: hanya pemilik (listener lain dan superadmin DITOLAK)', Gate::forUser($owner)->allows('track', $song)
            && Gate::forUser($other)->denies('track', $song)
            && Gate::forUser($admin)->denies('track', $song));

        $this->line('Logika koleksi (data uji di-rollback)');
        DB::beginTransaction();

        try {
            $a = $this->createUser('Uji Beranda A');
            $b = $this->createUser('Uji Beranda B');

            $artist = $a->artists()->create(['name' => 'Harry Uji']);
            $album  = $a->albums()->create(['artist_id' => $artist->id, 'name' => 'Fine Line Uji']);
            $a->albums()->create(['name' => 'Solo Uji']);

            $mk = fn (User $u, string $title, array $extra = []) => $u->songs()->create($extra + [
                'title'      => $title,
                'audio_path' => 'check/'.Str::random(6).'.mp3',
                'file_size'  => 1,
                'mime_type'  => 'audio/mpeg',
            ]);
            $inAlbum = ['album_id' => $album->id, 'artist_id' => $artist->id];

            $s1 = $mk($a, 'Adore You', $inAlbum + ['track_number' => 2]);
            $s2 = $mk($a, 'Golden', $inAlbum + ['track_number' => 1]);
            $s3 = $mk($a, 'Watermelon', $inAlbum);
            $s4 = $mk($a, 'Loose');
            $mk($b, 'Adore You Too');

            $a->playlists()->create(['name' => 'Playlist Uji']);
            $a->favorites()->create(['song_id' => $s2->id]);
            $a->favorites()->create(['song_id' => $s1->id]);

            $stats = $library->stats($a);
            $this->check('stats: lagu 4, album 2, artis 1, playlist 1, favorit 2', $stats === [
                'songs' => 4, 'albums' => 2, 'artists' => 1, 'playlists' => 1, 'favorites' => 2,
            ]);

            $sec = $library->sections($a);
            $this->check('sections memuat 7 bagian', array_keys($sec) === [
                'recentlyAdded', 'recentlyPlayed', 'albums', 'artists', 'playlists', 'favorites', 'allSongs',
            ]);
            $this->check('Recently Added: lagu terbaru (Loose) paling atas, total 4', $sec['recentlyAdded']->count() === 4
                && $sec['recentlyAdded']->first()->id === $s4->id);
            $this->check('Recently Played kosong sebelum ada yang diputar', $sec['recentlyPlayed']->isEmpty());

            DB::table('songs')->where('id', $s1->id)->update(['last_played_at' => now()->subMinutes(5)]);
            DB::table('songs')->where('id', $s2->id)->update(['last_played_at' => now()]);
            $sec = $library->sections($a);
            $this->check('Recently Played: Golden lalu Adore You (lagu lain tidak ikut)', $sec['recentlyPlayed']->pluck('id')->all() === [$s2->id, $s1->id]);
            $this->check('All Songs urut judul', $sec['allSongs']->pluck('title')->all() === ['Adore You', 'Golden', 'Loose', 'Watermelon']);
            $this->check('Favorite Songs: terbaru difavoritkan lebih dulu (Adore You, Golden)', $sec['favorites']->pluck('id')->all() === [$s1->id, $s2->id]);
            $this->check('Flag is_favorite benar (Golden true, Loose false)', (bool) $sec['recentlyAdded']->firstWhere('id', $s2->id)->is_favorite === true
                && (bool) $sec['recentlyAdded']->firstWhere('id', $s4->id)->is_favorite === false);

            $this->check('Album: urut track_number, tanpa nomor di paling bawah', $library->albumSongs($album, $a)->pluck('id')->all() === [$s2->id, $s1->id, $s3->id]);
            $this->check('Artis: lagu urut judul (Adore You, Golden, Watermelon)', $library->artistSongs($artist, $a)->pluck('title')->all() === ['Adore You', 'Golden', 'Watermelon']);

            $r = $library->search($a, 'harry');
            $this->check("search 'harry': 3 lagu (via artis), 1 album, 1 artis", $r['songs']->count() === 3 && $r['albums']->count() === 1 && $r['artists']->count() === 1);
            $r = $library->search($a, 'FINE');
            $this->check("search 'FINE': tidak peka huruf besar, 3 lagu (via album), 1 album", $r['songs']->count() === 3 && $r['albums']->count() === 1 && $r['artists']->count() === 0);
            $r = $library->search($a, 'adore');
            $this->check("search 'adore': hanya lagu milik sendiri (1)", $r['songs']->count() === 1 && $r['songs']->first()->id === $s1->id);
            $r = $library->search($a, 'playlist uji');
            $this->check("search 'playlist uji': 1 playlist", $r['playlists']->count() === 1);
            foreach (['%', '_', 'zzzzzz'] as $term) {
                $r = $library->search($a, $term);
                $this->check("search '{$term}': 0 hasil (bukan wildcard)", $r['songs']->isEmpty() && $r['albums']->isEmpty() && $r['artists']->isEmpty() && $r['playlists']->isEmpty());
            }

            $this->check('allSongs: filter album -> 3', $library->allSongs($a, ['album_id' => $album->id])->count() === 3);
            $this->check('allSongs: filter artis -> 3', $library->allSongs($a, ['artist_id' => $artist->id])->count() === 3);
            $this->check("allSongs: search 'loose' -> 1", $library->allSongs($a, ['search' => 'loose'])->count() === 1);
            $this->check('allSongs: batas limit dihormati', $library->allSongs($a, [], 2)->count() === 2);
            $this->check("favoriteSongs: search 'gold' -> 1", $library->favoriteSongs($a, 'gold')->count() === 1);

            $arr = $library->allSongs($a)->first()->toArray();
            $this->check('JSON lagu memuat stream_url dan cover_url, tanpa audio_path', array_key_exists('stream_url', $arr)
                && array_key_exists('cover_url', $arr) && ! array_key_exists('audio_path', $arr));

            $before = $s1->fresh()->updated_at;
            DB::table('songs')->where('id', $s1->id)->update(['last_played_at' => now()]);
            $this->check('Mencatat pemutaran tidak mengubah updated_at lagu', $before->equalTo($s1->fresh()->updated_at));
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
            'email'     => 'home+'.Str::lower(Str::random(8)).'@example.test',
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