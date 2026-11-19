<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateListenerCommand extends Command
{
    protected $signature = 'bs7:create-listener
                            {name : Nama listener}
                            {email : Email listener}
                            {--inactive : Buat akun dalam keadaan nonaktif}';

    protected $description = 'Membuat akun listener (untuk pengujian; password diminta lewat prompt)';

    public function handle(): int
    {
        $password = (string) $this->secret('Password listener (min. 8 karakter)');

        $validator = Validator::make([
            'name'     => $this->argument('name'),
            'email'    => $this->argument('email'),
            'password' => $password,
        ], [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User([
            'name'     => $this->argument('name'),
            'email'    => $this->argument('email'),
            'password' => $password,
        ]);

        $user->forceFill([
            'role'              => User::ROLE_LISTENER,
            'is_active'         => ! $this->option('inactive'),
            'email_verified_at' => now(),
        ])->save();

        $status = $user->is_active ? 'AKTIF' : 'NONAKTIF';
        $this->info("Listener dibuat: {$user->email} ({$status})");

        return self::SUCCESS;
    }
}