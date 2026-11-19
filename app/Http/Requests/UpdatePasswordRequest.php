<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'max:72', 'confirmed', 'different:current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'         => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini salah.',
            'password.required'                 => 'Password baru wajib diisi.',
            'password.min'                      => 'Password baru minimal 8 karakter.',
            'password.max'                      => 'Password baru maksimal 72 karakter.',
            'password.confirmed'                => 'Konfirmasi password tidak sama.',
            'password.different'                => 'Password baru harus berbeda dari password saat ini.',
        ];
    }
}