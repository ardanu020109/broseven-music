<?php

namespace App\Services;

use App\Models\Song;
use App\Support\AudioFormats;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class MusicStreamService
{
    public function __construct(private FileUploadService $files)
    {
    }

    /**
     * Streaming inline. BinaryFileResponse menangani HTTP Range (206 Partial Content)
     * sehingga seek pada elemen audio berfungsi.
     */
    public function stream(Song $song): BinaryFileResponse
    {
        return new BinaryFileResponse(
            $this->absolutePath($song),
            200,
            [
                'Content-Type'           => AudioFormats::contentTypeForPath($song->audio_path),
                'Accept-Ranges'          => 'bytes',
                'Cache-Control'          => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ],
            false,
            ResponseHeaderBag::DISPOSITION_INLINE,
        );
    }

    public function download(Song $song): BinaryFileResponse
    {
        $extension = pathinfo($song->audio_path, PATHINFO_EXTENSION);
        $filename  = $this->safeFilename($song->title).($extension ? ".{$extension}" : '');

        $response = new BinaryFileResponse(
            $this->absolutePath($song),
            200,
            [
                'Content-Type'           => AudioFormats::contentTypeForPath($song->audio_path),
                'Cache-Control'          => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
            false,
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        );

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            $this->asciiFallback($filename),
        );

        return $response;
    }

    private function absolutePath(Song $song): string
    {
        abort_unless($this->files->exists($song->audio_path), 404);

        return Storage::disk($this->files->disk())->path($song->audio_path);
    }

    private function safeFilename(string $title): string
    {
        $clean = trim((string) preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', '', $title));

        return $clean !== '' ? Str::limit($clean, 150, '') : 'lagu';
    }

    private function asciiFallback(string $filename): string
    {
        $fallback = trim(str_replace(['%', '/', '\\'], '', Str::ascii($filename)));

        return $fallback !== '' ? $fallback : 'lagu';
    }
}