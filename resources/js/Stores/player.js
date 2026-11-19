import { computed, ref } from 'vue';
import { defineStore } from 'pinia';

const KEYS = {
    volume: 'bs7.player.volume',
    muted: 'bs7.player.muted',
    shuffle: 'bs7.player.shuffle',
    repeat: 'bs7.player.repeat',
};

export const REPEAT_MODES = ['off', 'all', 'one'];

function readSetting(key, fallback) {
    try {
        const raw = window.localStorage.getItem(key);

        return raw === null ? fallback : JSON.parse(raw);
    } catch {
        return fallback;
    }
}

function writeSetting(key, value) {
    try {
        window.localStorage.setItem(key, JSON.stringify(value));
    } catch {
        // Penyimpanan tidak tersedia: abaikan
    }
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function shuffled(items) {
    const copy = [...items];

    for (let i = copy.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [copy[i], copy[j]] = [copy[j], copy[i]];
    }

    return copy;
}

/**
 * Urutan putar berupa daftar indeks antrean.
 * Saat shuffle, lagu awal selalu di posisi pertama dan sisanya diacak.
 */
function buildOrder(length, start, useShuffle) {
    const all = Array.from({ length }, (_, i) => i);

    if (!useShuffle) {
        return all;
    }

    const rest = shuffled(all.filter((i) => i !== start));

    return start >= 0 && start < length ? [start, ...rest] : rest;
}

function normalize(song) {
    return {
        id: song.id,
        title: song.title,
        artist: song.artist?.name ?? null,
        album: song.album?.name ?? null,
        cover_url: song.cover_url ?? null,
        stream_url: song.stream_url,
        download_url: song.download_url ?? null,
        duration: Number(song.duration) || 0,
    };
}

export const usePlayerStore = defineStore('player', () => {
    // Satu-satunya elemen audio di seluruh aplikasi (tidak ditempel ke DOM)
    const audio = new Audio();
    audio.preload = 'metadata';

    // ---------- State ----------
    const queue = ref([]); // lagu dalam urutan asli
    const order = ref([]); // urutan putar (indeks ke queue)
    const pos = ref(-1); // posisi saat ini di dalam order
    const playing = ref(false);
    const loading = ref(false);
    const error = ref(false);
    const currentTime = ref(0);
    const duration = ref(0);

    const savedVolume = Number(readSetting(KEYS.volume, 0.8));
    const volume = ref(Number.isFinite(savedVolume) ? clamp(savedVolume, 0, 1) : 0.8);
    const muted = ref(readSetting(KEYS.muted, false) === true);
    const shuffle = ref(readSetting(KEYS.shuffle, false) === true);
    const savedRepeat = readSetting(KEYS.repeat, 'off');
    const repeat = ref(REPEAT_MODES.includes(savedRepeat) ? savedRepeat : 'off');

    let errorStreak = 0;
    let skipTimer = null;

    // ---------- Getter ----------
    const currentIndex = computed(() => (pos.value >= 0 ? (order.value[pos.value] ?? -1) : -1));
    const current = computed(() => (currentIndex.value >= 0 ? (queue.value[currentIndex.value] ?? null) : null));
    const hasTrack = computed(() => current.value !== null);
    const hasNext = computed(
        () => hasTrack.value && (pos.value < order.value.length - 1 || repeat.value === 'all'),
    );
    const playOrder = computed(() => order.value.map((queueIndex, p) => ({ pos: p, song: queue.value[queueIndex] })));

    // ---------- Volume ----------
    function applyVolume() {
        audio.volume = volume.value;
        audio.muted = muted.value;
    }

    applyVolume();

    function setVolume(value) {
        volume.value = clamp(Number(value) || 0, 0, 1);

        if (volume.value > 0) {
            muted.value = false;
        }

        applyVolume();
        writeSetting(KEYS.volume, volume.value);
        writeSetting(KEYS.muted, muted.value);
    }

    function toggleMute() {
        if (volume.value === 0) {
            volume.value = 0.6;
            muted.value = false;
        } else {
            muted.value = !muted.value;
        }

        applyVolume();
        writeSetting(KEYS.volume, volume.value);
        writeSetting(KEYS.muted, muted.value);
    }

    // ---------- Pemutaran dasar ----------
    function safePlay() {
        let result;

        try {
            result = audio.play();
        } catch {
            return;
        }

        if (result && typeof result.catch === 'function') {
            result.catch((e) => {
                if (e?.name === 'AbortError') return; // src berganti saat memuat: wajar

                if (e?.name === 'NotAllowedError') {
                    playing.value = false;
                    loading.value = false;
                }
            });
        }
    }

    function loadCurrent(autoplay = true) {
        const song = current.value;

        if (!song) return;

        clearTimeout(skipTimer);
        error.value = false;
        loading.value = true;
        currentTime.value = 0;
        duration.value = song.duration || 0;

        audio.src = song.stream_url;
        audio.load();

        syncMediaSession();

        if (autoplay) {
            safePlay();
        }
    }

    function restart() {
        audio.currentTime = 0;
        currentTime.value = 0;
        safePlay();
    }

    function finishQueue() {
        audio.pause();
        audio.currentTime = 0;
        currentTime.value = 0;
        playing.value = false;
    }

    function reshuffle(lastIndex) {
        const next = shuffled(Array.from({ length: queue.value.length }, (_, i) => i));

        // Lagu yang baru selesai tidak boleh langsung diputar lagi
        if (next.length > 1 && next[0] === lastIndex) {
            const swap = next.length - 1;
            [next[0], next[swap]] = [next[swap], next[0]];
        }

        return next;
    }

    // ---------- Aksi antrean ----------
    function playQueue(songs, startIndex = null) {
        const list = (songs ?? []).filter((s) => s && s.stream_url).map(normalize);

        if (!list.length) return;

        let start = startIndex;

        if (start === null || start === undefined) {
            start = shuffle.value ? Math.floor(Math.random() * list.length) : 0;
        }

        start = clamp(start, 0, list.length - 1);

        queue.value = list;
        order.value = buildOrder(list.length, start, shuffle.value);
        pos.value = shuffle.value ? 0 : start;

        loadCurrent(true);
    }

    function playFrom(songs, index) {
        const song = songs?.[index];

        if (!song) return;

        // Menekan lagu yang sedang aktif = play/pause
        if (current.value?.id === song.id) {
            toggle();

            return;
        }

        playQueue(songs, index);
    }

    function playShuffled(songs) {
        setShuffle(true);
        playQueue(songs, null);
    }

    function playPosition(position) {
        if (position < 0 || position >= order.value.length) return;

        if (position === pos.value) {
            toggle();

            return;
        }

        pos.value = position;
        loadCurrent(true);
    }

    function toggle() {
        if (!current.value) return;

        if (audio.paused) {
            if (error.value) {
                loadCurrent(true);
            } else {
                safePlay();
            }
        } else {
            audio.pause();
        }
    }

    function stop() {
        if (!current.value) return;

        audio.pause();
        audio.currentTime = 0;
        currentTime.value = 0;
        playing.value = false;
    }

    function next(auto = false) {
        if (!current.value) return;

        if (pos.value < order.value.length - 1) {
            pos.value += 1;
            loadCurrent(true);

            return;
        }

        if (repeat.value === 'all') {
            if (shuffle.value && order.value.length > 1) {
                order.value = reshuffle(currentIndex.value);
            }

            pos.value = 0;
            loadCurrent(true);

            return;
        }

        if (auto) {
            finishQueue();
        }
    }

    function prev() {
        if (!current.value) return;

        // Sudah berjalan lebih dari 3 detik: ulang dari awal
        if (audio.currentTime > 3) {
            restart();

            return;
        }

        if (pos.value > 0) {
            pos.value -= 1;
            loadCurrent(true);

            return;
        }

        if (repeat.value === 'all' && order.value.length > 1) {
            pos.value = order.value.length - 1;
            loadCurrent(true);

            return;
        }

        restart();
    }

    function seek(seconds) {
        if (!current.value) return;

        const max = duration.value || Number.POSITIVE_INFINITY;
        const target = clamp(Number(seconds) || 0, 0, max);

        audio.currentTime = target;
        currentTime.value = target;
    }

    function setShuffle(value) {
        if (shuffle.value === value) return;

        shuffle.value = value;
        writeSetting(KEYS.shuffle, value);

        if (!queue.value.length) return;

        const cur = currentIndex.value;

        order.value = buildOrder(queue.value.length, cur, value);
        pos.value = value ? (cur >= 0 ? 0 : -1) : cur;
    }

    function toggleShuffle() {
        setShuffle(!shuffle.value);
    }

    function setRepeat(mode) {
        if (!REPEAT_MODES.includes(mode)) return;

        repeat.value = mode;
        writeSetting(KEYS.repeat, mode);
    }

    function cycleRepeat() {
        setRepeat(REPEAT_MODES[(REPEAT_MODES.indexOf(repeat.value) + 1) % REPEAT_MODES.length]);
    }

    /**
     * Dipanggil saat sebuah lagu dihapus dari koleksi.
     */
    function removeSong(id) {
        const idx = queue.value.findIndex((s) => s.id === id);

        if (idx === -1) return;

        if (current.value?.id === id) {
            close();

            return;
        }

        const curIdx = currentIndex.value;

        queue.value = queue.value.filter((_, i) => i !== idx);
        order.value = order.value.filter((i) => i !== idx).map((i) => (i > idx ? i - 1 : i));
        pos.value = order.value.indexOf(curIdx > idx ? curIdx - 1 : curIdx);
    }

    function close() {
        clearTimeout(skipTimer);
        audio.pause();
        audio.removeAttribute('src');
        audio.load();

        queue.value = [];
        order.value = [];
        pos.value = -1;
        playing.value = false;
        loading.value = false;
        error.value = false;
        currentTime.value = 0;
        duration.value = 0;
        errorStreak = 0;

        syncMediaSession();
    }

    // ---------- Event audio ----------
    function syncDuration() {
        const d = audio.duration;

        duration.value = Number.isFinite(d) && d > 0 ? d : (current.value?.duration ?? 0);
    }

    function setPlaybackState(state) {
        if (!hasMediaSession) return;

        try {
            navigator.mediaSession.playbackState = state;
        } catch {
            // Tidak kritis
        }
    }

    audio.addEventListener('loadstart', () => {
        loading.value = true;
    });
    audio.addEventListener('loadedmetadata', () => {
        syncDuration();
        loading.value = false;
    });
    audio.addEventListener('durationchange', syncDuration);
    audio.addEventListener('canplay', () => {
        loading.value = false;
    });
    audio.addEventListener('waiting', () => {
        loading.value = true;
    });
    audio.addEventListener('playing', () => {
        errorStreak = 0;
        error.value = false;
        loading.value = false;
    });
    audio.addEventListener('play', () => {
        playing.value = true;
        setPlaybackState('playing');
    });
    audio.addEventListener('pause', () => {
        playing.value = false;
        setPlaybackState('paused');
    });
    audio.addEventListener('timeupdate', () => {
        if (!audio.seeking) {
            currentTime.value = audio.currentTime;
        }
    });
    audio.addEventListener('ended', () => {
        if (!current.value) return;

        if (repeat.value === 'one') {
            restart();

            return;
        }

        next(true);
    });
    audio.addEventListener('error', () => {
        if (!current.value) return;

        loading.value = false;
        playing.value = false;
        error.value = true;
        errorStreak += 1;

        clearTimeout(skipTimer);

        // Lewati lagu yang gagal dimuat (berhenti jika semuanya gagal)
        if (pos.value < order.value.length - 1 && errorStreak < order.value.length) {
            const failedPos = pos.value;

            skipTimer = setTimeout(() => {
                if (error.value && pos.value === failedPos) {
                    next(true);
                }
            }, 1200);
        }
    });

    // ---------- Media Session (tombol media keyboard / layar kunci) ----------
    const hasMediaSession = typeof navigator !== 'undefined' && 'mediaSession' in navigator;

    function setAction(action, handler) {
        try {
            navigator.mediaSession.setActionHandler(action, handler);
        } catch {
            // Aksi tidak didukung browser ini
        }
    }

    function syncMediaSession() {
        if (!hasMediaSession) return;

        const song = current.value;

        try {
            if (!song || typeof MediaMetadata === 'undefined') {
                navigator.mediaSession.metadata = null;

                return;
            }

            navigator.mediaSession.metadata = new MediaMetadata({
                title: song.title,
                artist: song.artist ?? '',
                album: song.album ?? '',
                artwork: song.cover_url ? [{ src: new URL(song.cover_url, window.location.origin).href }] : [],
            });
        } catch {
            // Tidak kritis
        }
    }

    if (hasMediaSession) {
        setAction('play', () => {
            if (current.value) safePlay();
        });
        setAction('pause', () => audio.pause());
        setAction('previoustrack', () => prev());
        setAction('nexttrack', () => {
            if (hasNext.value) next();
        });
        setAction('stop', () => stop());
        setAction('seekto', (details) => {
            if (typeof details?.seekTime === 'number') seek(details.seekTime);
        });
    }

    return {
        // state
        queue, order, pos, playing, loading, error, currentTime, duration, volume, muted, shuffle, repeat,
        // getter
        currentIndex, current, hasTrack, hasNext, playOrder,
        // aksi
        playQueue, playFrom, playShuffled, playPosition, toggle, stop, next, prev, seek,
        setVolume, toggleMute, setShuffle, toggleShuffle, setRepeat, cycleRepeat, removeSong, close,
    };
});