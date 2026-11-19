<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreListenerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $name  = $this->input('name');
        $email = $this->input('email');

        $this->merge([
            'name'  => is_string($name) ? trim($name) : $name,
            'email' => is_string($email) ? Str::lower(trim($email)) : $email,
        ]);
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100'],
            'email'     => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'max:72'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Nama wajib diisi.',
            'name.max'          => 'Nama maksimal 100 karakter.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.max'         => 'Email maksimal 190 karakter.',
            'email.unique'      => 'Email sudah dipakai akun lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 8 karakter.',
            'password.max'      => 'Password maksimal 72 karakter.',
            'is_active.required' => 'Status akun wajib dipilih.',
            'is_active.boolean'  => 'Status akun tidak valid.',
        ];
    }
}