<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPlaylistSongRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('playlist')) ?? false;
    }

    public function rules(): array
    {
        return [
            'song_id' => [
                'required',
                'integer',
                // Hanya lagu milik user sendiri
                Rule::exists('songs', 'id')->where('user_id', $this->user()->id),
                // Tidak boleh duplikat di playlist ini
                Rule::unique('playlist_song', 'song_id')->where('playlist_id', $this->route('playlist')->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'song_id.required' => 'Pilih lagu terlebih dahulu.',
            'song_id.integer'  => 'Lagu yang dipilih tidak valid.',
            'song_id.exists'   => 'Lagu yang dipilih tidak valid.',
            'song_id.unique'   => 'Lagu sudah ada di playlist ini.',
        ];
    }
}