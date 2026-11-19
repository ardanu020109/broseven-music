<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Profil milik sendiri (superadmin dan listener). Selalu memakai user yang sedang login.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render($user->isSuperadmin() ? 'Admin/Profile' : 'Listener/Profile', [
            'profile' => $user->only(['id', 'name', 'email', 'role', 'created_at', 'last_login_at']),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated())->save();

        return redirect()->route('profile.edit')->with('status', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->password = $request->validated('password');
        $user->setRememberToken(Str::random(60));
        $user->save();

        return redirect()->route('profile.edit')->with('status', 'Password berhasil diganti.');
    }
}