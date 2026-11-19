<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DevFillCommand extends Command
{
    protected $signature = 'bs7:dev-fill
                            {email : Email listener}
                            {--count=15 : Jumlah artis dan album uji}
                            {--clear : Hapus data uji ber-awalan [Uji]}';

    protected $description = 'KHUSUS PENGUJIAN: isi/hapus data uji artis dan album (tanpa file gambar)';

    public function handle(): int
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

        if ($this->option('clear')) {
            $albums  = $user->albums()->where('name', 'like', '[Uji]%')->delete();
            $artists = $user->artists()->where('name', 'like', '[Uji]%')->delete();
            $this->info("Dihapus: {$artists} artis dan {$albums} album uji.");

            return self::SUCCESS;
        }

        $count = max(1, min(100, (int) $this->option('count')));
        $artistIds = [];

        for ($i = 1; $i <= $count; $i++) {
            $label = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $artistIds[] = $user->artists()->create(['name' => "[Uji] Artis {$label}"])->id;
        }

        for ($i = 1; $i <= $count; $i++) {
            $label = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $user->albums()->create([
                'artist_id'    => $artistIds[($i - 1) % count($artistIds)],
                'name'         => "[Uji] Album {$label}",
                'release_year' => 2000 + $i,
            ]);
        }

        $this->info("Dibuat: {$count} artis dan {$count} album uji untuk {$user->email}.");

        return self::SUCCESS;
    }
}