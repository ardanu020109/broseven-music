<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import PlaylistSongList from '../../../Components/PlaylistSongList.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import SingleSelect from '../../../Components/SingleSelect.vue';
import { toggleFavorite } from '../../../Composables/useFavorite';
import { formatDuration } from '../../../Composables/useFormat';
import { usePlayerStore } from '../../../Stores/player';

defineOptions({ layout: ListenerLayout });

const props = defineProps({
    playlist: { type: Object, required: true },
    songs: { type: Array, default: () => [] },
    addOptions: { type: Array, default: () => [] },
});

const page = usePage();
const player = usePlayerStore();

// Salinan lokal agar urutan langsung berubah saat diseret/ditekan (optimistic)
const items = ref([...props.songs]);

watch(
    () => props.songs,
    (value) => {
        items.value = [...value];
    },
);

const totalDuration = computed(() =>
    items.value.reduce((sum, song) => sum + (Number(song.duration) || 0), 0),
);

// ---- Tambah lagu ----
const addForm = useForm({ song_id: '' });

function addSong() {
    if (!addForm.song_id) {
        addForm.setError('song_id', 'Pilih lagu terlebih dahulu.');
        return;
    }

    addForm.clearErrors();
    addForm.post(`/playlists/${props.playlist.id}/songs`, {
        preserveScroll: true,
        onSuccess: () => addForm.reset('song_id'),
    });
}

// ---- Urutan ----
const saving = ref(false);

function reorder(order) {
    const byId = new Map(items.value.map((song) => [song.id, song]));
    const previous = items.value;

    items.value = order.map((id) => byId.get(id)).filter(Boolean);
    saving.value = true;

    router.put(
        `/playlists/${props.playlist.id}/songs/reorder`,
        { order },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['songs', 'errors'],
            onError: () => {
                items.value = previous;
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

// ---- Pemutar ----
function play(song) {
    player.playFrom(items.value, items.value.findIndex((s) => s.id === song.id));
}

function playAll() {
    player.playQueue(items.value);
}

function playShuffled() {
    player.playShuffled(items.value);
}

// ---- Keluarkan lagu ----
const songToRemove = ref(null);
const removing = ref(false);

function removeSong() {
    if (!songToRemove.value) return;

    const id = songToRemove.value.id;
    removing.value = true;

    router.delete(`/playlists/${props.playlist.id}/songs/${id}`, {
        preserveScroll: true,
        onFinish: () => {
            removing.value = false;
            songToRemove.value = null;
        },
    });
}

// ---- Hapus playlist ----
const showDelete = ref(false);
const deleting = ref(false);

function destroyPlaylist() {
    deleting.value = true;

    router.delete(`/playlists/${props.playlist.id}`, {
        onFinish: () => {
            deleting.value = false;
            showDelete.value = false;
        },
    });
}
</script>

<template>
    <Head :title="playlist.name" />

    <div>
        <Link href="/playlists" class="small text-decoration-none">
            <i class="bi bi-arrow-left"></i> Semua playlist
        </Link>

        <div class="bs7-playlist-head mt-3 mb-4">
            <div class="bs7-playlist-cover">
                <img v-if="playlist.cover_url" :src="playlist.cover_url" :alt="playlist.name" />
                <span v-else class="bs7-placeholder"><i class="bi bi-music-note-list"></i></span>
            </div>

            <div class="min-w-0 flex-grow-1">
                <div class="small text-uppercase text-secondary" style="letter-spacing: 0.08em;">Playlist</div>
                <h1 class="h2 fw-bold mb-1 text-break">{{ playlist.name }}</h1>
                <p v-if="playlist.description" class="text-secondary mb-2 text-break">{{ playlist.description }}</p>
                <div class="small text-secondary mb-3">
                    {{ items.length }} lagu
                    <template v-if="items.length"> &middot; {{ formatDuration(totalDuration) }}</template>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary" :disabled="!items.length" @click="playAll">
                        <i class="bi bi-play-fill me-1"></i> Putar Playlist
                    </button>
                    <button type="button" class="btn btn-outline-primary" :disabled="!items.length" @click="playShuffled">
                        <i class="bi bi-shuffle me-1"></i> Acak
                    </button>
                    <Link :href="`/playlists/${playlist.id}/edit`" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </Link>
                    <button type="button" class="btn btn-outline-danger" @click="showDelete = true">
                        <i class="bi bi-trash me-1"></i> Hapus
                    </button>
                </div>
            </div>
        </div>

        <div class="bs7-add-panel mb-4">
            <label for="add-song" class="form-label fw-semibold">Tambah lagu ke playlist</label>
            <div class="d-flex flex-wrap gap-2">
                <div class="flex-grow-1" style="min-width: 220px;">
                    <SingleSelect
                        id="add-song"
                        v-model="addForm.song_id"
                        :options="addOptions"
                        placeholder="Cari dan pilih lagu..."
                        search-placeholder="Cari judul atau artis..."
                        empty-text="Semua lagu Anda sudah ada di playlist ini"
                        :clearable="false"
                        :invalid="!!addForm.errors.song_id"
                    />
                </div>
                <button type="button" class="btn btn-primary" :disabled="addForm.processing" @click="addSong">
                    <span v-if="addForm.processing" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-plus-lg me-1"></i> Tambah
                </button>
            </div>
            <div v-if="addForm.errors.song_id" class="invalid-feedback d-block">{{ addForm.errors.song_id }}</div>
            <div v-if="!addOptions.length && !items.length" class="form-text">
                Anda belum punya lagu.
                <Link href="/songs/create">Unggah lagu</Link> terlebih dahulu.
            </div>
        </div>

        <div v-if="page.props.errors?.order" class="alert alert-danger py-2">{{ page.props.errors.order }}</div>

        <PlaylistSongList
            v-if="items.length"
            :songs="items"
            :current-id="player.current?.id ?? null"
            :playing="player.playing"
            :disabled="saving"
            @play="play"
            @favorite="toggleFavorite($event, ['songs'])"
            @remove="songToRemove = $event"
            @reorder="reorder"
        />

        <div v-else class="bs7-page-card bs7-empty">
            <i class="bi bi-music-note-list d-block mb-3"></i>
            <h2 class="h5">Playlist masih kosong</h2>
            <p class="mb-0">Pilih lagu pada kotak di atas lalu klik Tambah.</p>
        </div>
    </div>

    <ConfirmModal
        :show="!!songToRemove"
        title="Keluarkan lagu?"
        :message="songToRemove ? `Lagu ini akan dikeluarkan dari playlist: ${songToRemove.title}. File lagunya tidak terhapus dan tetap ada di koleksi Anda.` : ''"
        confirm-text="Keluarkan"
        :processing="removing"
        @confirm="removeSong"
        @cancel="songToRemove = null"
    />

    <ConfirmModal
        :show="showDelete"
        title="Hapus playlist?"
        :message="`Playlist ini akan dihapus: ${playlist.name}. Lagu di dalamnya tidak ikut terhapus dari koleksi Anda.`"
        :processing="deleting"
        @confirm="destroyPlaylist"
        @cancel="showDelete = false"
    />
</template>