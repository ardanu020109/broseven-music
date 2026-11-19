<?php

namespace App\Http\Requests;

use App\Models\Album;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Album::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:150'],
            'artist_id' => [
                'nullable', 'integer',
                Rule::exists('artists', 'id')->where('user_id', $this->user()->id),
            ],
            'release_year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'description'  => ['nullable', 'string', 'max:2000'],
            'cover'        => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Nama album wajib diisi.',
            'name.max'             => 'Nama album maksimal 150 karakter.',
            'artist_id.exists'     => 'Artis yang dipilih tidak valid.',
            'release_year.integer' => 'Tahun rilis harus berupa angka.',
            'release_year.min'     => 'Tahun rilis minimal 1900.',
            'release_year.max'     => 'Tahun rilis tidak boleh melebihi tahun depan.',
            'description.max'      => 'Deskripsi maksimal 2000 karakter.',
            'cover.image'          => 'File cover harus berupa gambar.',
            'cover.mimes'          => 'Cover harus berformat JPG, PNG, atau WEBP.',
            'cover.max'            => 'Ukuran cover maksimal 2 MB.',
            'cover.uploaded'       => 'Cover gagal diunggah. Pastikan ukurannya maksimal 2 MB.',
        ];
    }
}