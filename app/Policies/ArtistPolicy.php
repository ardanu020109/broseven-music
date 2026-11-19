<?php

namespace App\Policies;

use App\Models\Artist;
use App\Models\User;

class ArtistPolicy extends ListenerOwnedPolicy
{
    /**
     * Foto artis boleh dilihat pemilik dan superadmin (monitoring).
     */
    public function photo(User $user, Artist $artist): bool
    {
        return $user->isSuperadmin() || $this->owns($user, $artist);
    }
}