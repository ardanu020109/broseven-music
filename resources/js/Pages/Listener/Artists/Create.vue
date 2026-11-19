<script setup>
import { Head } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import ArtistForm from '../../../Components/ArtistForm.vue';

defineOptions({ layout: ListenerLayout });
</script>

<template>
    <Head title="Tambah Artis" />

    <div class="bs7-page-card">
        <h1 class="h4 fw-bold mb-4">Tambah Artis</h1>
        <ArtistForm />
    </div>
</template>