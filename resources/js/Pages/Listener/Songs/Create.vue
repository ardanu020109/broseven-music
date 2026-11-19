<script setup>
import { Head } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import SongForm from '../../../Components/SongForm.vue';

defineOptions({ layout: ListenerLayout });

defineProps({
    albumOptions: { type: Array, default: () => [] },
    artistOptions: { type: Array, default: () => [] },
    maxMb: { type: Number, default: 50 },
    formats: { type: String, default: '' },
});
</script>

<template>
    <Head title="Unggah Lagu" />

    <div class="bs7-page-card">
        <h1 class="h4 fw-bold mb-4">Unggah Lagu</h1>
        <SongForm
            :album-options="albumOptions"
            :artist-options="artistOptions"
            :max-mb="maxMb"
            :formats="formats"
        />
    </div>
</template>