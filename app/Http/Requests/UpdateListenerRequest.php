<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateListenerRequest extends StoreListenerRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('listener')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'email', 'max:190',
                Rule::unique('users', 'email')->ignore($this->route('listener')->id),
            ],
            'password'  => ['nullable', 'string', 'min:8', 'max:72'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}