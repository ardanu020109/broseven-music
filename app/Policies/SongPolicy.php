<?php

namespace App\Policies;

use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SongPolicy extends ListenerOwnedPolicy
{
    /**
     * Listener melihat daftar lagunya; superadmin memantau semua lagu.
     */
    public function viewAny(User $user): bool
    {
        return $user->isListener() || $user->isSuperadmin();
    }

    public function view(User $user, Model $model): bool
    {
        return $user->isSuperadmin() || $this->owns($user, $model);
    }

    public function play(User $user, Song $song): bool
    {
        return $this->view($user, $song);
    }

    public function download(User $user, Song $song): bool
    {
        return $this->view($user, $song);
    }

    /**
     * Favorite hanya untuk lagu milik listener sendiri.
     */
    public function favorite(User $user, Song $song): bool
    {
        return $this->owns($user, $song);
    }

    /**
     * Mencatat "terakhir diputar": hanya pemilik lagu (bukan superadmin).
     */
    public function track(User $user, Song $song): bool
    {
        return $this->owns($user, $song);
    }

    // update() dan delete() diwarisi: hanya listener pemilik. Superadmin DITOLAK.
}