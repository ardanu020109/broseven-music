<script setup>
import { Head } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import PlaylistForm from '../../../Components/PlaylistForm.vue';

defineOptions({ layout: ListenerLayout });

defineProps({
    playlist: { type: Object, required: true },
});
</script>

<template>
    <Head title="Edit Playlist" />

    <div class="bs7-page-card">
        <h1 class="h4 fw-bold mb-4">Edit Playlist</h1>
        <PlaylistForm :playlist="playlist" />
    </div>
</template>