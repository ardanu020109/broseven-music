import { router } from '@inertiajs/vue3';

/**
 * Toggle favorit. `only` = nama prop halaman yang dimuat ulang (partial reload),
 * mis. ['songs'], agar pembaruan cepat dan halaman tidak berkedip.
 */
export function toggleFavorite(song, only = []) {
    router.post(
        `/songs/${song.id}/favorite`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            ...(only.length ? { only } : {}),
        },
    );
}