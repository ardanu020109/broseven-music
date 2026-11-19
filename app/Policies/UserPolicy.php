<?php

namespace App\Policies;

use App\Models\User;

/**
 * Manajemen listener: hanya superadmin, dan hanya terhadap akun listener
 * (superadmin tidak bisa mengubah/menghapus akun superadmin lain atau dirinya).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isSuperadmin() && $target->isListener();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isSuperadmin() && $target->isListener();
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isSuperadmin() && $target->isListener();
    }
}