<?php

namespace App\Http\Requests;

use App\Models\Artist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArtistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Artist::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('artists', 'name')->where('user_id', $this->user()->id),
            ],
            'biography' => ['nullable', 'string', 'max:2000'],
            'photo'     => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Nama artis wajib diisi.',
            'name.max'        => 'Nama artis maksimal 150 karakter.',
            'name.unique'     => 'Nama artis sudah ada di koleksi Anda.',
            'biography.max'   => 'Biografi maksimal 2000 karakter.',
            'photo.image'     => 'File foto harus berupa gambar.',
            'photo.mimes'     => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'photo.max'       => 'Ukuran foto maksimal 2 MB.',
            'photo.uploaded'  => 'Foto gagal diunggah. Pastikan ukurannya maksimal 2 MB.',
        ];
    }
}