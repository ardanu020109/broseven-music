<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlaylistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('playlist')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:150'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'cover'        => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_cover' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Nama playlist wajib diisi.',
            'name.max'         => 'Nama playlist maksimal 150 karakter.',
            'description.max'  => 'Deskripsi maksimal 2000 karakter.',
            'cover.image'      => 'File cover harus berupa gambar.',
            'cover.mimes'      => 'Cover harus berformat JPG, PNG, atau WEBP.',
            'cover.max'        => 'Ukuran cover maksimal 2 MB.',
            'cover.uploaded'   => 'Cover gagal diunggah. Pastikan ukurannya maksimal 2 MB.',
        ];
    }
}