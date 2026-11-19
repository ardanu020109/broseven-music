<?php

namespace App\Http\Requests;

use App\Models\Song;
use App\Rules\AudioFile;
use App\Support\AudioFormats;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSongRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Song::class) ?? false;
    }

    protected function metadataRules(): array
    {
        $userId = $this->user()->id;

        return [
            'title'     => ['required', 'string', 'max:200'],
            'album_id'  => ['nullable', 'integer', Rule::exists('albums', 'id')->where('user_id', $userId)],
            'artist_id' => ['nullable', 'integer', Rule::exists('artists', 'id')->where('user_id', $userId)],
            'track_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'duration'     => ['nullable', 'integer', 'min:0', 'max:86400'],
        ];
    }

    public function rules(): array
    {
        return $this->metadataRules() + [
            'audio' => ['bail', 'required', 'file', 'max:'.config('broseven.audio.max_kb'), new AudioFile()],
        ];
    }

    public function messages(): array
    {
        $mb = (int) floor(config('broseven.audio.max_kb') / 1024);

        return [
            'title.required'       => 'Judul lagu wajib diisi.',
            'title.max'            => 'Judul lagu maksimal 200 karakter.',
            'album_id.exists'      => 'Album yang dipilih tidak valid.',
            'artist_id.exists'     => 'Artis yang dipilih tidak valid.',
            'track_number.integer' => 'Nomor track harus berupa angka.',
            'track_number.min'     => 'Nomor track minimal 1.',
            'track_number.max'     => 'Nomor track maksimal 999.',
            'audio.required'       => 'File musik wajib dipilih.',
            'audio.file'           => 'File musik tidak valid.',
            'audio.max'            => "Ukuran file musik maksimal {$mb} MB.",
            'audio.uploaded'       => 'File musik gagal diunggah. Periksa batas upload_max_filesize dan post_max_size di php.ini.',
        ];
    }
}