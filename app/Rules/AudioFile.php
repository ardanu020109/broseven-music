<?php

namespace App\Rules;

use App\Support\AudioFormats;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class AudioFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('File musik gagal diunggah. Periksa batas upload di server.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (! in_array($extension, AudioFormats::extensions(), true)) {
            $fail('Format file harus '.AudioFormats::label().'.');

            return;
        }

        // Deteksi dari ISI file, bukan dari nama/klien
        $mime = (string) $value->getMimeType();

        if (! in_array($mime, AudioFormats::DETECTED_MIMES, true)) {
            $fail("Isi file tidak dikenali sebagai audio yang didukung (terdeteksi: {$mime}).");
        }
    }
}