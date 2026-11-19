<script setup>
import { Head } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import AlbumForm from '../../../Components/AlbumForm.vue';

defineOptions({ layout: ListenerLayout });

defineProps({
    artistOptions: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Tambah Album" />

    <div class="bs7-page-card">
        <h1 class="h4 fw-bold mb-4">Tambah Album</h1>
        <AlbumForm :artist-options="artistOptions" />
    </div>
</template>