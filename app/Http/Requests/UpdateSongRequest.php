<?php

namespace App\Http\Requests;

use App\Rules\AudioFile;

class UpdateSongRequest extends StoreSongRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('song')) ?? false;
    }

    public function rules(): array
    {
        return $this->metadataRules() + [
            'audio' => ['bail', 'nullable', 'file', 'max:'.config('broseven.audio.max_kb'), new AudioFile()],
        ];
    }
}