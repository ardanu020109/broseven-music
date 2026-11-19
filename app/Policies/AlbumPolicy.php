<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;

class AlbumPolicy extends ListenerOwnedPolicy
{
    /**
     * Cover album boleh dilihat pemilik dan superadmin (monitoring).
     */
    public function cover(User $user, Album $album): bool
    {
        return $user->isSuperadmin() || $this->owns($user, $album);
    }
}