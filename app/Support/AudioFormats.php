<?php

namespace App\Support;

/**
 * Daftar putih format audio. Ekstensi -> Content-Type yang dilayani server.
 */
final class AudioFormats
{
    public const TYPES = [
        'mp3'  => 'audio/mpeg',
        'wav'  => 'audio/wav',
        'ogg'  => 'audio/ogg',
        'oga'  => 'audio/ogg',
        'flac' => 'audio/flac',
        'm4a'  => 'audio/mp4',
        'aac'  => 'audio/aac',
    ];

    /**
     * MIME hasil deteksi isi file (finfo) yang dianggap audio sah.
     */
    public const DETECTED_MIMES = [
        'audio/mpeg', 'audio/mp3', 'audio/x-mpeg', 'audio/mpeg3', 'audio/x-mpeg-3',
        'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/vnd.wave',
        'audio/ogg', 'application/ogg', 'audio/vorbis',
        'audio/flac', 'audio/x-flac',
        'audio/mp4', 'audio/x-m4a', 'audio/m4a', 'video/mp4',
        'audio/aac', 'audio/x-aac', 'audio/x-hx-aac-adts',
    ];

    public static function extensions(): array
    {
        return array_keys(self::TYPES);
    }

    public static function contentType(?string $extension): ?string
    {
        return self::TYPES[strtolower((string) $extension)] ?? null;
    }

    public static function contentTypeForPath(string $path): string
    {
        return self::contentType(pathinfo($path, PATHINFO_EXTENSION)) ?? 'application/octet-stream';
    }

    public static function label(): string
    {
        return 'MP3, WAV, OGG, FLAC, M4A, AAC';
    }
}