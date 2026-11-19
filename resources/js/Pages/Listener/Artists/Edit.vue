<script setup>
import { Head } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import ArtistForm from '../../../Components/ArtistForm.vue';

defineOptions({ layout: ListenerLayout });

defineProps({
    artist: { type: Object, required: true },
});
</script>

<template>
    <Head title="Edit Artis" />

    <div class="bs7-page-card">
        <h1 class="h4 fw-bold mb-4">Edit Artis</h1>
        <ArtistForm :artist="artist" />
    </div>
</template>