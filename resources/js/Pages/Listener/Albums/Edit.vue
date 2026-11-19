<script setup>
import { Head } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import AlbumForm from '../../../Components/AlbumForm.vue';

defineOptions({ layout: ListenerLayout });

defineProps({
    album: { type: Object, required: true },
    artistOptions: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Edit Album" />

    <div class="bs7-page-card">
        <h1 class="h4 fw-bold mb-4">Edit Album</h1>
        <AlbumForm :album="album" :artist-options="artistOptions" />
    </div>
</template>