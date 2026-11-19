<script setup>
import { Head } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import PlaylistForm from '../../../Components/PlaylistForm.vue';

defineOptions({ layout: ListenerLayout });
</script>

<template>
    <Head title="Buat Playlist" />

    <div class="bs7-page-card">
        <h1 class="h4 fw-bold mb-4">Buat Playlist</h1>
        <PlaylistForm />
    </div>
</template>