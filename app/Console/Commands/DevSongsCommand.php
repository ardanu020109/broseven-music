<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DevSongsCommand extends Command
{
    protected $signature = 'bs7:dev-songs
                            {email : Email listener}
                            {--count=12 : Jumlah lagu uji}
                            {--clear : Hapus lagu uji ber-awalan [Uji] beserta filenya}';

    protected $description = 'KHUSUS PENGUJIAN: menggandakan satu lagu asli menjadi banyak lagu uji, atau menghapusnya';

    public function handle(FileUploadService $files): int
    {
        if (app()->isProduction()) {
            $this->error('Perintah ini dinonaktifkan di production.');

            return self::FAILURE;
        }

        $user = User::listeners()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Listener tidak ditemukan.');

            return self::FAILURE;
        }

        $disk = $files->disk();

        if ($this->option('clear')) {
            $count = 0;

            foreach ($user->songs()->where('title', 'like', '[Uji]%')->get() as $song) {
                $files->delete($song->audio_path);
                $song->delete();
                $count++;
            }

            $this->info("Dihapus: {$count} lagu uji (file ikut dihapus).");

            return self::SUCCESS;
        }

        $source = $user->songs()->where('title', 'not like', '[Uji]%')->orderBy('id')->first();

        if (! $source || ! $files->exists($source->audio_path)) {
            $this->error('Upload minimal satu lagu asli lewat halaman My Songs terlebih dahulu.');

            return self::FAILURE;
        }

        $count = max(1, min(60, (int) $this->option('count')));
        $extension = pathinfo($source->audio_path, PATHINFO_EXTENSION);

        for ($i = 1; $i <= $count; $i++) {
            $label = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $path  = "songs/{$user->id}/".Str::random(40).".{$extension}";

            Storage::disk($disk)->copy($source->audio_path, $path);

            $user->songs()->create([
                'album_id'     => $source->album_id,
                'artist_id'    => $source->artist_id,
                'title'        => "[Uji] Lagu {$label}",
                'track_number' => $i,
                'audio_path'   => $path,
                'duration'     => $source->duration,
                'file_size'    => $source->file_size,
                'mime_type'    => $source->mime_type,
            ]);
        }

        $this->info("Dibuat: {$count} lagu uji untuk {$user->email} (salinan dari '{$source->title}').");

        return self::SUCCESS;
    }
}