<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Dasar untuk data koleksi listener: hanya LISTENER PEMILIK yang boleh
 * melihat/mengubah/menghapus. Superadmin tidak punya akses ke master data.
 */
abstract class ListenerOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isListener();
    }

    public function view(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->isListener();
    }

    public function update(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    protected function owns(User $user, Model $model): bool
    {
        return $user->isListener() && (int) $model->user_id === (int) $user->id;
    }
}