<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import ArtistCard from '../../../Components/ArtistCard.vue';
import Pagination from '../../../Components/Pagination.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { debounce } from '../../../Composables/useDebounce';

defineOptions({ layout: ListenerLayout });

const props = defineProps({
    artists: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');

const applySearch = debounce((value) => {
    router.get(
        '/artists',
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
    router.delete(`/artists/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            toDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Artists" />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-0">Artists</h1>
            <div class="text-secondary small">{{ artists.total }} artis dalam koleksi Anda</div>
        </div>
        <Link href="/artists/create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Tambah Artis
        </Link>
    </div>

    <div class="input-group mb-4" style="max-width: 420px;">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input v-model="search" type="search" class="form-control" placeholder="Cari nama artis..." />
    </div>

    <div v-if="artists.data.length" class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3 mb-4">
        <div v-for="artist in artists.data" :key="artist.id" class="col">
            <ArtistCard :artist="artist" @delete="toDelete = $event" />
        </div>
    </div>

    <div v-else class="bs7-page-card bs7-empty">
        <i class="bi bi-mic d-block mb-3"></i>
        <template v-if="filters.search">
            <h2 class="h5">Tidak ada hasil</h2>
            <p class="mb-0">Tidak ada artis yang cocok dengan pencarian "{{ filters.search }}".</p>
        </template>
        <template v-else>
            <h2 class="h5">Belum ada artis</h2>
            <p>Mulai dengan menambahkan artis pertama Anda.</p>
            <Link href="/artists/create" class="btn btn-primary">Tambah Artis</Link>
        </template>
    </div>

    <Pagination :links="artists.links" />

    <ConfirmModal
        :show="!!toDelete"
        title="Hapus artis?"
        :message="toDelete ? `Artis &quot;${toDelete.name}&quot; akan dihapus. Album dan lagu miliknya tidak ikut terhapus, hanya tidak lagi memiliki artis.`.replaceAll('&quot;', String.fromCharCode(34)) : ''"
        :processing="deleting"
        @confirm="destroy"
        @cancel="toDelete = null"
    />
</template>