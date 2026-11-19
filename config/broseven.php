<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Superadmin (dibaca oleh SuperAdminSeeder)
    |--------------------------------------------------------------------------
    | Nilai diambil dari .env. Jangan hardcode kredensial di sini.
    */
    'admin' => [
        'name'     => env('ADMIN_NAME'),
        'email'    => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Disk penyimpanan media (foto artis, cover album, audio)
    |--------------------------------------------------------------------------
    | Harus disk PRIVAT. File dilayani lewat route berotorisasi, bukan URL publik.
    */
    'media_disk' => env('BS7_MEDIA_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Audio
    |--------------------------------------------------------------------------
    | max_kb: ukuran maksimal file musik (KB). Default 51200 = 50 MB.
    | Pastikan upload_max_filesize dan post_max_size di php.ini lebih besar.
    */
    'audio' => [
        'max_kb' => (int) env('BS7_AUDIO_MAX_KB', 51200),
    ],

];