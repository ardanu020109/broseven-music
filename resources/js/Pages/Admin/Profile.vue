<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import ProfileForm from '../../Components/ProfileForm.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    profile: { type: Object, required: true },
});
</script>

<template>
    <Head title="Profile" />

    <div>
        <h1 class="h3 fw-bold mb-4">Profile</h1>
        <ProfileForm :profile="profile" :email-editable="false" />
    </div>
</template>