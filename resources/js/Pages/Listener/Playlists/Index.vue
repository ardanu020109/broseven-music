<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import PlaylistCard from '../../../Components/PlaylistCard.vue';
import Pagination from '../../../Components/Pagination.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { debounce } from '../../../Composables/useDebounce';

defineOptions({ layout: ListenerLayout });

const props = defineProps({
    playlists: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');

const applySearch = debounce((value) => {
    router.get(
        '/playlists',
        { search: value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 350);

watch(search, (value) => applySearch(value));

const toDelete = ref(null);
const deleting = ref(false);

function destroy() {
    if (!toDelete.value) return;

    deleting.value = true;
    router.delete(`/playlists/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            toDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Playlists" />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-0">Playlists</h1>
            <div class="text-secondary small">{{ playlists.total }} playlist dalam koleksi Anda</div>
        </div>
        <Link href="/playlists/create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Buat Playlist
        </Link>
    </div>

    <div class="input-group mb-4" style="max-width: 420px;">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input v-model="search" type="search" class="form-control" placeholder="Cari nama playlist..." />
    </div>

    <div v-if="playlists.data.length" class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3 mb-4">
        <div v-for="playlist in playlists.data" :key="playlist.id" class="col">
            <PlaylistCard :playlist="playlist" @delete="toDelete = $event" />
        </div>
    </div>

    <div v-else class="bs7-page-card bs7-empty">
        <i class="bi bi-music-note-list d-block mb-3"></i>
        <template v-if="filters.search">
            <h2 class="h5">Tidak ada hasil</h2>
            <p class="mb-0">Tidak ada playlist yang cocok dengan pencarian Anda.</p>
        </template>
        <template v-else>
            <h2 class="h5">Belum ada playlist</h2>
            <p>Buat playlist pertama untuk mengelompokkan lagu favorit Anda.</p>
            <Link href="/playlists/create" class="btn btn-primary">Buat Playlist</Link>
        </template>
    </div>

    <Pagination :links="playlists.links" />

    <ConfirmModal
        :show="!!toDelete"
        title="Hapus playlist?"
        :message="toDelete ? `Playlist ini akan dihapus: ${toDelete.name}. Lagu di dalamnya tidak ikut terhapus dari koleksi Anda.` : ''"
        :processing="deleting"
        @confirm="destroy"
        @cancel="toDelete = null"
    />
</template>