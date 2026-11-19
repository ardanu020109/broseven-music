<?php

namespace App\Policies;

use App\Models\Playlist;
use App\Models\User;

class PlaylistPolicy extends ListenerOwnedPolicy
{
    /**
     * Cover playlist boleh dilihat pemilik dan superadmin (monitoring).
     */
    public function cover(User $user, Playlist $playlist): bool
    {
        return $user->isSuperadmin() || $this->owns($user, $playlist);
    }
}