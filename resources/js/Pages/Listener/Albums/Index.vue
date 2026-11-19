<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import AlbumCard from '../../../Components/AlbumCard.vue';
import Pagination from '../../../Components/Pagination.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import SingleSelect from '../../../Components/SingleSelect.vue';
import { debounce } from '../../../Composables/useDebounce';

defineOptions({ layout: ListenerLayout });

const props = defineProps({
    albums: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    artistOptions: { type: Array, default: () => [] },
});

const search = ref(props.filters.search ?? '');
const artistId = ref(props.filters.artist_id ?? '');

function load() {
    router.get(
        '/albums',
        {
            search: search.value || undefined,
            artist_id: artistId.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const debouncedLoad = debounce(load, 350);

watch(search, () => debouncedLoad());
watch(artistId, () => load());

const toDelete = ref(null);
const deleting = ref(false);

function destroy() {
    if (!toDelete.value) return;

    deleting.value = true;
    router.delete(`/albums/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            toDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Albums" />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-0">Albums</h1>
            <div class="text-secondary small">{{ albums.total }} album dalam koleksi Anda</div>
        </div>
        <Link href="/albums/create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Tambah Album
        </Link>
    </div>

    <div class="row g-2 mb-4" style="max-width: 720px;">
        <div class="col-sm-7">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input v-model="search" type="search" class="form-control" placeholder="Cari nama album..." />
            </div>
        </div>
        <div class="col-sm-5">
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

    <div v-if="albums.data.length" class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3 mb-4">
        <div v-for="album in albums.data" :key="album.id" class="col">
            <AlbumCard :album="album" @delete="toDelete = $event" />
        </div>
    </div>

    <div v-else class="bs7-page-card bs7-empty">
        <i class="bi bi-disc d-block mb-3"></i>
        <template v-if="filters.search || filters.artist_id">
            <h2 class="h5">Tidak ada hasil</h2>
            <p class="mb-0">Tidak ada album yang cocok dengan pencarian atau filter Anda.</p>
        </template>
        <template v-else>
            <h2 class="h5">Belum ada album</h2>
            <p>Mulai dengan menambahkan album pertama Anda.</p>
            <Link href="/albums/create" class="btn btn-primary">Tambah Album</Link>
        </template>
    </div>

    <Pagination :links="albums.links" />

    <ConfirmModal
        :show="!!toDelete"
        title="Hapus album?"
        :message="toDelete ? `Album ini akan dihapus: ${toDelete.name}. Lagu di dalamnya tidak ikut terhapus, hanya tidak lagi memiliki album.` : ''"
        :processing="deleting"
        @confirm="destroy"
        @cancel="toDelete = null"
    />
</template>