<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import ListenerForm from '../../../Components/ListenerForm.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    listener: { type: Object, required: true },
});
</script>

<template>
    <Head title="Edit Listener" />

    <div class="bs7-page-card">
        <h1 class="h4 fw-bold mb-4">Edit Listener</h1>
        <ListenerForm :listener="listener" />
    </div>
</template>