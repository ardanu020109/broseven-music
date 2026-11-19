<?php

namespace App\Console\Commands;

use App\Models\Song;
use App\Models\User;
use App\Rules\AudioFile;
use App\Services\FileUploadService;
use App\Services\MusicStreamService;
use App\Support\AudioFormats;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SongCheckCommand extends Command
{
    protected $signature = 'bs7:song-check';

    protected $description = 'Memeriksa rute, validasi audio, streaming Range, dan policy lagu (Phase 5)';

    private bool $allOk = true;

    public function handle(FileUploadService $files, MusicStreamService $stream): int
    {
        $this->line('Rute');
        foreach (['songs.index', 'songs.create', 'songs.store', 'songs.edit', 'songs.update', 'songs.destroy', 'music.stream', 'music.download'] as $name) {
            $this->check("Rute '{$name}' terdaftar", Route::has($name));
        }

        $this->line('Lingkungan PHP');
        $this->check('Ekstensi fileinfo aktif (deteksi isi file)', extension_loaded('fileinfo'));

        $maxBytes  = (int) config('broseven.audio.max_kb') * 1024;
        $uploadRaw = (string) ini_get('upload_max_filesize');
        $postRaw   = (string) ini_get('post_max_size');
        $upload    = ini_parse_quantity($uploadRaw);
        $post      = ini_parse_quantity($postRaw);
        $maxMb     = (int) floor($maxBytes / 1048576);

        $this->line('  php.ini: '.(php_ini_loaded_file() ?: '(tidak ada)'));
        $this->line("  upload_max_filesize={$uploadRaw}, post_max_size={$postRaw}, batas aplikasi={$maxMb}M");

        if ($upload < $maxBytes || ($post > 0 && $post < $maxBytes)) {
            $this->line('  <fg=yellow>[!]</> Batas PHP lebih kecil dari batas aplikasi. Upload lagu besar akan gagal. Naikkan upload_max_filesize dan post_max_size di php.ini.');
        } else {
            $this->line('  <fg=green>[OK]</> Batas upload PHP cukup untuk batas aplikasi');
        }

        $this->line('Validasi file audio (isi dan ekstensi)');
        $this->check('mp3 + audio/mpeg DITERIMA', $this->rulePasses('lagu.mp3', 'audio/mpeg'));
        $this->check('wav + audio/x-wav DITERIMA', $this->rulePasses('lagu.wav', 'audio/x-wav'));
        $this->check('flac + audio/flac DITERIMA', $this->rulePasses('lagu.flac', 'audio/flac'));
        $this->check('ogg + audio/ogg DITERIMA', $this->rulePasses('lagu.ogg', 'audio/ogg'));
        $this->check('m4a + video/mp4 DITERIMA', $this->rulePasses('lagu.m4a', 'video/mp4'));
        $this->check('exe DITOLAK', ! $this->rulePasses('virus.exe', 'application/x-msdownload'));
        $this->check('php DITOLAK', ! $this->rulePasses('shell.php', 'text/x-php'));
        $this->check('ekstensi mp3 tetapi isi bukan audio DITOLAK', ! $this->rulePasses('palsu.mp3', 'text/plain'));
        $this->check('isi audio tetapi ekstensi txt DITOLAK', ! $this->rulePasses('lagu.txt', 'audio/mpeg'));
        $this->check('Content-Type m4a -> audio/mp4', AudioFormats::contentTypeForPath('songs/1/abc.m4a') === 'audio/mp4');

        $this->line('Streaming dan download');
        $diskName = $files->disk();
        $relative = 'bs7-check/'.Str::random(8).'.mp3';

        try {
            Storage::disk($diskName)->put($relative, str_repeat('A', 100));
            $song = (new Song())->forceFill(['id' => 1, 'user_id' => 1, 'title' => 'Uji: Lagu/Aneh', 'audio_path' => $relative]);

            $full = $stream->stream($song);
            $full->prepare(Request::create('/music/1/stream', 'GET'));
            $this->check('Tanpa Range -> 200 dan Accept-Ranges: bytes', $full->getStatusCode() === 200
                && $full->headers->get('Accept-Ranges') === 'bytes');
            $this->check('Content-Type audio/mpeg', str_starts_with((string) $full->headers->get('Content-Type'), 'audio/mpeg'));
            $cache = (string) $full->headers->get('Cache-Control');
            $this->check('Cache-Control private (bukan public)', str_contains($cache, 'private') && ! str_contains($cache, 'public'));

            $part = $stream->stream($song);
            $part->prepare(Request::create('/music/1/stream', 'GET', [], [], [], ['HTTP_RANGE' => 'bytes=0-9']));
            $this->check('Range bytes=0-9 -> 206, Content-Range bytes 0-9/100', $part->getStatusCode() === 206
                && $part->headers->get('Content-Range') === 'bytes 0-9/100'
                && (string) $part->headers->get('Content-Length') === '10');

            $tail = $stream->stream($song);
            $tail->prepare(Request::create('/music/1/stream', 'GET', [], [], [], ['HTTP_RANGE' => 'bytes=90-']));
            $this->check('Range bytes=90- -> 206, Content-Range bytes 90-99/100', $tail->getStatusCode() === 206
                && $tail->headers->get('Content-Range') === 'bytes 90-99/100');

            $bad = $stream->stream($song);
            $bad->prepare(Request::create('/music/1/stream', 'GET', [], [], [], ['HTTP_RANGE' => 'bytes=500-600']));
            $this->check('Range di luar ukuran file -> 416', $bad->getStatusCode() === 416);

            $download = $stream->download($song);
            $disposition = (string) $download->headers->get('Content-Disposition');
            $this->check('Download -> Content-Disposition attachment', str_starts_with($disposition, 'attachment'));
            $this->check('Nama download tidak memuat karakter terlarang (/ atau :)', ! str_contains($disposition, 'Uji:') && ! str_contains($disposition, 'Lagu/'));
        } catch (\Throwable $e) {
            $this->check('Pengujian streaming ('.$e->getMessage().')', false);
        } finally {
            Storage::disk($diskName)->deleteDirectory('bs7-check');
        }

        $this->line('Policy lagu');
        $owner = $this->makeUser(1, User::ROLE_LISTENER);
        $other = $this->makeUser(2, User::ROLE_LISTENER);
        $admin = $this->makeUser(3, User::ROLE_SUPERADMIN);
        $song  = (new Song())->forceFill(['user_id' => 1]);

        foreach (['play', 'download'] as $ability) {
            $this->check("Pemilik boleh {$ability}", Gate::forUser($owner)->allows($ability, $song));
            $this->check("Superadmin boleh {$ability}", Gate::forUser($admin)->allows($ability, $song));
            $this->check("Listener lain DITOLAK {$ability}", Gate::forUser($other)->denies($ability, $song));
        }
        $this->check('Superadmin DITOLAK update/delete', Gate::forUser($admin)->denies('update', $song)
            && Gate::forUser($admin)->denies('delete', $song));

        if ($this->allOk) {
            $this->info('SEMUA PENGECEKAN LULUS');

            return self::SUCCESS;
        }

        $this->error('ADA PENGECEKAN YANG GAGAL (lihat tanda X di atas)');

        return self::FAILURE;
    }

    private function rulePasses(string $filename, string $mime): bool
    {
        $file = UploadedFile::fake()->create($filename, 10, $mime);

        return Validator::make(['audio' => $file], ['audio' => [new AudioFile()]])->passes();
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