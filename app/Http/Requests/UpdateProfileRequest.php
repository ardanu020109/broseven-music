<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
        $rules = [
            'name' => ['required', 'string', 'max:100'],
        ];

        // Email superadmin bersumber dari .env (ADMIN_EMAIL), tidak diubah dari sini
        if (! $this->user()->isSuperadmin()) {
            $rules['email'] = [
                'required', 'string', 'email', 'max:190',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Nama wajib diisi.',
            'name.max'       => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'email.max'      => 'Email maksimal 190 karakter.',
            'email.unique'   => 'Email sudah dipakai akun lain.',
        ];
    }
}