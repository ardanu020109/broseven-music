<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import ListenerForm from '../../../Components/ListenerForm.vue';

defineOptions({ layout: AdminLayout });
</script>

<template>
    <Head title="Tambah Listener" />

    <div class="bs7-page-card">
        <h1 class="h4 fw-bold mb-4">Tambah Listener</h1>
        <ListenerForm />
    </div>
</template>