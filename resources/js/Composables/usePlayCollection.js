import { usePlayerStore } from '../Stores/player';
import { useToastStore } from '../Stores/toast';

let busy = false;

function cleanQuery(query) {
    return Object.fromEntries(
        Object.entries(query ?? {}).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    );
}

export async function fetchQueue(url, query = {}) {
    const response = await window.axios.get(url, {
        params: cleanQuery(query),
        headers: { Accept: 'application/json' },
    });

    return Array.isArray(response.data?.songs) ? response.data.songs : [];
}

/**
 * Memuat daftar lagu sebuah koleksi lalu memutarnya sebagai antrean.
 * options: { query, shuffle, startSong, fallback }
 */
export async function playCollection(url, options = {}) {
    const { query = {}, shuffle = false, startSong = null, fallback = null } = options;

    if (busy) return;

    busy = true;

    const player = usePlayerStore();
    const toast = useToastStore();

    try {
        let songs;

        try {
            songs = await fetchQueue(url, query);
        } catch (error) {
            const status = error?.response?.status;

            if (status === 401 || status === 419) {
                toast.show('Sesi Anda berakhir. Silakan login kembali.', 'warning');

                return;
            }

            if (!fallback?.length) {
                toast.show('Gagal memuat daftar putar. Coba lagi.', 'error');

                return;
            }

            songs = fallback;
        }

        if (!songs.length) {
            toast.show('Tidak ada lagu untuk diputar.', 'warning');

            return;
        }

        if (shuffle) {
            player.playShuffled(songs);

            return;
        }

        if (startSong) {
            let index = songs.findIndex((s) => s.id === startSong.id);

            // Lagu di luar batas antrean: tetap diputar lebih dulu
            if (index === -1) {
                songs = [startSong, ...songs];
                index = 0;
            }

            player.playQueue(songs, index);

            return;
        }

        player.playQueue(songs);
    } finally {
        busy = false;
    }
}

/**
 * Klik sebuah lagu: jika sedang aktif -> jeda/lanjut; jika tidak -> putar dari lagu itu
 * di dalam seluruh koleksi (bukan hanya halaman yang tampil).
 */
export async function playFromCollection(url, song, options = {}) {
    const player = usePlayerStore();

    if (player.current?.id === song.id) {
        player.toggle();

        return;
    }

    await playCollection(url, { ...options, startSong: song });
}