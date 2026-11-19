<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ListenerService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DevListenersCommand extends Command
{
    protected $signature = 'bs7:dev-listeners
                            {--count=12 : Jumlah listener uji}
                            {--clear : Hapus listener uji beserta datanya}';

    protected $description = 'KHUSUS PENGUJIAN: membuat/menghapus listener uji (password acak, tidak untuk login)';

    public function handle(ListenerService $listeners): int
    {
        if (app()->isProduction()) {
            $this->error('Perintah ini dinonaktifkan di production.');

            return self::FAILURE;
        }

        if ($this->option('clear')) {
            $count = 0;

            User::query()->listeners()->where('email', 'like', 'uji.listener%@example.test')->get()
                ->each(function (User $user) use ($listeners, &$count) {
                    $listeners->delete($user);
                    $count++;
                });

            $this->info("Dihapus: {$count} listener uji.");

            return self::SUCCESS;
        }

        $count = max(1, min(60, (int) $this->option('count')));
        $created = 0;

        for ($i = 1; $i <= $count; $i++) {
            $label = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $email = "uji.listener{$label}@example.test";

            if (User::query()->where('email', $email)->exists()) {
                continue;
            }

            $listeners->create([
                'name'      => "[Uji] Listener {$label}",
                'email'     => $email,
                'password'  => Str::random(24),
                'is_active' => $i % 4 !== 0, // setiap kelipatan 4 dibuat nonaktif
            ]);
            $created++;
        }

        $this->info("Dibuat: {$created} listener uji (kelipatan 4 berstatus nonaktif).");

        return self::SUCCESS;
    }
}