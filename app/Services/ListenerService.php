<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pengelolaan akun listener oleh superadmin.
 */
class ListenerService
{
    public function __construct(private FileUploadService $files)
    {
    }

    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;

        return User::query()
            ->listeners()
            ->when(filled($search), function ($query) use ($search) {
                $like = '%'.addcslashes((string) $search, '%_\\').'%';

                $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->withCount(['songs', 'albums', 'artists', 'playlists'])
            ->withSum('songs as songs_size', 'file_size')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        $user = new User([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);

        // role dan is_active tidak mass-assignable: diisi eksplisit di sini
        $user->forceFill([
            'role'              => User::ROLE_LISTENER,
            'is_active'         => (bool) ($data['is_active'] ?? true),
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    public function update(User $listener, array $data): User
    {
        $listener->fill([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        $passwordChanged = filled($data['password'] ?? null);

        if ($passwordChanged) {
            $listener->password = $data['password'];
            $listener->setRememberToken(Str::random(60));
        }

        $active = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : (bool) $listener->is_active;
        $listener->is_active = $active;

        $listener->save();

        if (! $active || $passwordChanged) {
            $this->revokeSessions($listener);
        }

        return $listener;
    }

    public function setActive(User $listener, bool $active): User
    {
        $listener->is_active = $active;
        $listener->save();

        if (! $active) {
            $this->revokeSessions($listener);
        }

        return $listener;
    }

    /**
     * Hapus listener beserta seluruh datanya dan file fisiknya.
     * Baris database dihapus oleh cascade; file dibersihkan setelah transaksi.
     */
    public function delete(User $listener): void
    {
        $id = $listener->id;

        $paths = collect()
            ->merge($listener->songs()->pluck('audio_path'))
            ->merge($listener->artists()->pluck('photo'))
            ->merge($listener->albums()->pluck('cover_image'))
            ->merge($listener->playlists()->pluck('cover_image'))
            ->filter()
            ->unique()
            ->values();

        DB::transaction(function () use ($listener) {
            DB::table('sessions')->where('user_id', $listener->id)->delete();
            $listener->delete();
        });

        foreach ($paths as $path) {
            $this->files->delete($path);
        }

        $disk = Storage::disk($this->files->disk());

        foreach (['songs', 'artists', 'albums', 'playlists'] as $directory) {
            $disk->deleteDirectory("{$directory}/{$id}");
        }
    }

    public function stats(User $listener): array
    {
        return [
            'songs'     => $listener->songs()->count(),
            'albums'    => $listener->albums()->count(),
            'artists'   => $listener->artists()->count(),
            'playlists' => $listener->playlists()->count(),
            'favorites' => $listener->favorites()->count(),
            'storage'   => (int) $listener->songs()->sum('file_size'),
        ];
    }

    public function recentSongs(User $listener, int $limit = 10): Collection
    {
        return $listener->songs()
            ->with(['album', 'artist', 'user:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Mengeluarkan listener dari semua perangkat.
     */
    private function revokeSessions(User $listener): void
    {
        DB::table('sessions')->where('user_id', $listener->id)->delete();
    }
}