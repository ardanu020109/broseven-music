<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import SongList from '../../../Components/SongList.vue';
import Pagination from '../../../Components/Pagination.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import SingleSelect from '../../../Components/SingleSelect.vue';
import { debounce } from '../../../Composables/useDebounce';
import { toggleFavorite } from '../../../Composables/useFavorite';
import { usePlayerStore } from '../../../Stores/player';
import { playCollection, playFromCollection } from '../../../Composables/usePlayCollection';

defineOptions({ layout: ListenerLayout });

const props = defineProps({
    songs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    albumOptions: { type: Array, default: () => [] },
    artistOptions: { type: Array, default: () => [] },
});

const player = usePlayerStore();

const search = ref(props.filters.search ?? '');
const albumId = ref(props.filters.album_id ?? '');
const artistId = ref(props.filters.artist_id ?? '');

function load() {
    router.get(
        '/songs',
        {
            search: search.value || undefined,
            album_id: albumId.value || undefined,
            artist_id: artistId.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const debouncedLoad = debounce(load, 350);

watch(search, () => debouncedLoad());
watch(albumId, () => load());
watch(artistId, () => load());

// ---- Pemutar ----
function play(song) {
    const list = props.songs.data;

    playFromCollection('/queue/songs', song, { query: collectionQuery(), fallback: list });
}

function collectionQuery() {
    return {
        search: props.filters.search || undefined,
        album_id: props.filters.album_id || undefined,
        artist_id: props.filters.artist_id || undefined,
    };
}

function playAll() {
    playCollection('/queue/songs', { query: collectionQuery() });
}

function playShuffled() {
    playCollection('/queue/songs', { query: collectionQuery(), shuffle: true });
}

// ---- Hapus ----
const toDelete = ref(null);
const deleting = ref(false);

function destroy() {
    if (!toDelete.value) return;

    const id = toDelete.value.id;
    deleting.value = true;

    router.delete(`/songs/${id}`, {
        preserveScroll: true,
        onSuccess: () => player.removeSong(id),
        onFinish: () => {
            deleting.value = false;
            toDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="My Songs" />

    <div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-0">My Songs</h1>
                <div class="text-secondary small">{{ songs.total }} lagu dalam koleksi Anda</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <template v-if="songs.data.length">
                    <button type="button" class="btn btn-outline-primary" @click="playAll">
                        <i class="bi bi-play-fill me-1"></i> Putar Semua
                    </button>
                    <button type="button" class="btn btn-outline-primary" @click="playShuffled">
                        <i class="bi bi-shuffle me-1"></i> Acak
                    </button>
                </template>
                <Link href="/songs/create" class="btn btn-primary">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Unggah Lagu
                </Link>
            </div>
        </div>

        <div class="row g-2 mb-4" style="max-width: 900px;">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input v-model="search" type="search" class="form-control" placeholder="Cari judul lagu..." />
                </div>
            </div>
            <div class="col-md-4 col-6">
                <SingleSelect
                    v-model="albumId"
                    :options="albumOptions"
                    placeholder="Semua album"
                    empty-label="Semua album"
                    aria-label="Filter album"
                    search-placeholder="Cari album..."
                    empty-text="Belum ada album"
                />
            </div>
            <div class="col-md-3 col-6">
                <SingleSelect
                    v-model="artistId"
                    :options="artistOptions"
                    placeholder="Semua artis"
                    empty-label="Semua artis"
                    aria-label="Filter artis"
                    search-placeholder="Cari artis..."
                    empty-text="Belum ada artis"
                />
            </div>
        </div>

        <SongList
            v-if="songs.data.length"
            class="mb-4"
            :songs="songs.data"
            :current-id="player.current?.id ?? null"
            :playing="player.playing"
            @play="play"
            @favorite="toggleFavorite($event, ['songs'])"
            @delete="toDelete = $event"
        />

        <div v-else class="bs7-page-card bs7-empty mb-4">
            <i class="bi bi-music-note-beamed d-block mb-3"></i>
            <template v-if="filters.search || filters.album_id || filters.artist_id">
                <h2 class="h5">Tidak ada hasil</h2>
                <p class="mb-0">Tidak ada lagu yang cocok dengan pencarian atau filter Anda.</p>
            </template>
            <template v-else>
                <h2 class="h5">Belum ada lagu</h2>
                <p>Unggah lagu pertama Anda untuk mulai membangun koleksi.</p>
                <Link href="/songs/create" class="btn btn-primary">Unggah Lagu</Link>
            </template>
        </div>

        <Pagination :links="songs.links" />
    </div>

    <ConfirmModal
        :show="!!toDelete"
        title="Hapus lagu?"
        :message="toDelete ? `Lagu ini akan dihapus beserta file audionya: ${toDelete.title}. Lagu juga akan hilang dari playlist dan favorit.` : ''"
        :processing="deleting"
        @confirm="destroy"
        @cancel="toDelete = null"
    />
</template>