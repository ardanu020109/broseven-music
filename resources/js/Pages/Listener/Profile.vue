<script setup>
import { Head } from '@inertiajs/vue3';
import ListenerLayout from '../../Layouts/ListenerLayout.vue';
import ProfileForm from '../../Components/ProfileForm.vue';

defineOptions({ layout: ListenerLayout });

defineProps({
    profile: { type: Object, required: true },
});
</script>

<template>
    <Head title="Profile" />

    <div>
        <h1 class="h3 fw-bold mb-4">Profile</h1>
        <ProfileForm :profile="profile" :email-editable="true" />
    </div>
</template>