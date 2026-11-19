<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FileUploadService
{
    public function disk(): string
    {
        return config('broseven.media_disk', 'local');
    }

    /**
     * Simpan gambar dengan nama acak (ekstensi ditentukan dari isi file, bukan dari klien).
     */
    public function storeImage(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, $this->disk());

        if ($path === false) {
            throw new RuntimeException('Gagal menyimpan file gambar.');
        }

        return $path;
    }

    /**
     * Simpan audio dengan nama acak. $extension HARUS berasal dari daftar putih server.
     */
    public function storeAudio(UploadedFile $file, string $directory, string $extension): string
    {
        $name = Str::random(40).'.'.$extension;
        $path = $file->storeAs($directory, $name, $this->disk());

        if ($path === false) {
            throw new RuntimeException('Gagal menyimpan file audio.');
        }

        return $path;
    }

    public function exists(?string $path): bool
    {
        return filled($path) && Storage::disk($this->disk())->exists($path);
    }

    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk($this->disk())->delete($path);
    }
}