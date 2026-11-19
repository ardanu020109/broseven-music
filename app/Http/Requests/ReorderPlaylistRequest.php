<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderPlaylistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('playlist')) ?? false;
    }

    public function rules(): array
    {
        return [
            'order'   => ['required', 'array', 'min:1', 'max:5000'],
            'order.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'order.required'   => 'Urutan tidak valid.',
            'order.array'      => 'Urutan tidak valid.',
            'order.*.integer'  => 'Urutan tidak valid.',
            'order.*.distinct' => 'Urutan tidak valid.',
        ];
    }
}