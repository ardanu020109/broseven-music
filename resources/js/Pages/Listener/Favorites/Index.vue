<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import SongList from '../../../Components/SongList.vue';
import Pagination from '../../../Components/Pagination.vue';
import { debounce } from '../../../Composables/useDebounce';
import { toggleFavorite } from '../../../Composables/useFavorite';
import { usePlayerStore } from '../../../Stores/player';
import { playCollection, playFromCollection } from '../../../Composables/usePlayCollection';

defineOptions({ layout: ListenerLayout });

const props = defineProps({
    songs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const player = usePlayerStore();

const search = ref(props.filters.search ?? '');

const applySearch = debounce((value) => {
    router.get(
        '/favorites',
        { search: value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 350);

watch(search, (value) => applySearch(value));

function play(song) {
    const list = props.songs.data;

    playFromCollection('/queue/favorites', song, { query: { search: props.filters.search }, fallback: list });
}

function playAll() {
    playCollection('/queue/favorites', { query: { search: props.filters.search } });
}

function playShuffled() {
    playCollection('/queue/favorites', { query: { search: props.filters.search }, shuffle: true });
}
</script>

<template>
    <Head title="Favorites" />

    <div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-0">
                    <i class="bi bi-heart-fill me-1" style="color: var(--bs7-pink);"></i> Favorites
                </h1>
                <div class="text-secondary small">{{ songs.total }} lagu favorit</div>
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

        <div class="input-group mb-4" style="max-width: 420px;">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input v-model="search" type="search" class="form-control" placeholder="Cari lagu favorit..." />
        </div>

        <SongList
            v-if="songs.data.length"
            class="mb-4"
            :songs="songs.data"
            :current-id="player.current?.id ?? null"
            :playing="player.playing"
            :show-actions="false"
            @play="play"
            @favorite="toggleFavorite($event, ['songs'])"
        />

        <div v-else class="bs7-page-card bs7-empty mb-4">
            <i class="bi bi-heart d-block mb-3" style="color: var(--bs7-pink);"></i>
            <template v-if="filters.search">
                <h2 class="h5">Tidak ada hasil</h2>
                <p class="mb-0">Tidak ada lagu favorit yang cocok dengan pencarian Anda.</p>
            </template>
            <template v-else>
                <h2 class="h5">Belum ada lagu favorit</h2>
                <p>Tekan ikon hati pada sebuah lagu untuk menambahkannya ke sini.</p>
                <Link href="/songs" class="btn btn-primary">Buka My Songs</Link>
            </template>
        </div>

        <Pagination :links="songs.links" />
    </div>
</template>