<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AdminSongList from '../../../Components/AdminSongList.vue';
import Pagination from '../../../Components/Pagination.vue';
import SingleSelect from '../../../Components/SingleSelect.vue';
import { debounce } from '../../../Composables/useDebounce';
import { playCollection, playFromCollection } from '../../../Composables/usePlayCollection';
import { usePlayerStore } from '../../../Stores/player';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    songs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    ownerOptions: { type: Array, default: () => [] },
});

const player = usePlayerStore();

const search = ref(props.filters.search ?? '');
const ownerId = ref(props.filters.owner_id ?? '');

function load() {
    router.get(
        '/admin/songs',
        { search: search.value || undefined, owner_id: ownerId.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const debouncedLoad = debounce(load, 350);

watch(search, () => debouncedLoad());
watch(ownerId, () => load());

// Antrean mengikuti filter yang sedang diterapkan
function collectionQuery() {
    return {
        search: props.filters.search || undefined,
        owner_id: props.filters.owner_id || undefined,
    };
}

function play(song) {
    playFromCollection('/admin/queue/songs', song, { query: collectionQuery(), fallback: props.songs.data });
}

function playAll() {
    playCollection('/admin/queue/songs', { query: collectionQuery() });
}

function playShuffled() {
    playCollection('/admin/queue/songs', { query: collectionQuery(), shuffle: true });
}
</script>

<template>
    <Head title="Uploaded Music" />

    <div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-0">Uploaded Music</h1>
                <div class="text-secondary small">{{ songs.total }} lagu dari semua listener (hanya lihat, putar, dan unduh)</div>
            </div>
            <div v-if="songs.data.length" class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary" @click="playAll">
                    <i class="bi bi-play-fill me-1"></i> Putar Semua
                </button>
                <button type="button" class="btn btn-outline-primary" @click="playShuffled">
                    <i class="bi bi-shuffle me-1"></i> Acak
                </button>
            </div>
        </div>

        <div class="row g-2 mb-4" style="max-width: 820px;">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input v-model="search" type="search" class="form-control" placeholder="Cari judul, artis, album, atau pemilik..." />
                </div>
            </div>
            <div class="col-md-5">
                <SingleSelect
                    v-model="ownerId"
                    :options="ownerOptions"
                    placeholder="Semua listener"
                    empty-label="Semua listener"
                    aria-label="Filter pemilik"
                    search-placeholder="Cari listener..."
                    empty-text="Belum ada listener"
                />
            </div>
        </div>

        <AdminSongList
            v-if="songs.data.length"
            class="mb-4"
            :songs="songs.data"
            :current-id="player.current?.id ?? null"
            :playing="player.playing"
            @play="play"
        />

        <div v-else class="bs7-page-card bs7-empty mb-4">
            <i class="bi bi-music-note-beamed d-block mb-3"></i>
            <template v-if="filters.search || filters.owner_id">
                <h2 class="h5">Tidak ada hasil</h2>
                <p class="mb-0">Tidak ada lagu yang cocok dengan pencarian atau filter Anda.</p>
            </template>
            <template v-else>
                <h2 class="h5">Belum ada musik</h2>
                <p class="mb-0">Belum ada listener yang mengunggah lagu.</p>
            </template>
        </div>

        <Pagination :links="songs.links" />
    </div>
</template>