<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Membuat superadmin dari variabel .env (ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD).
     * Nama dan password hanya diisi saat akun BARU dibuat; menjalankan ulang seeder
     * tidak menimpa nama atau password yang sudah diubah lewat halaman Profile.
     */
    public function run(): void
    {
        $name     = config('broseven.admin.name');
        $email    = config('broseven.admin.email');
        $password = config('broseven.admin.password');

        if (blank($name) || blank($email) || blank($password)) {
            $this->command?->error('ADMIN_NAME, ADMIN_EMAIL, dan ADMIN_PASSWORD wajib diisi di file .env');

            return;
        }

        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name = $name;
            $user->password = $password; // di-hash otomatis oleh cast 'hashed'
        }

        $user->role = User::ROLE_SUPERADMIN;
        $user->is_active = true;

        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        $this->command?->info("Superadmin siap: {$user->email}");
    }
}