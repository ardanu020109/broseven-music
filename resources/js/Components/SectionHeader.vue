<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, required: true },
    icon: { type: String, default: '' },
    href: { type: String, default: '' },
    linkText: { type: String, default: 'Lihat semua' },
});
</script>

<template>
    <div class="bs7-section-head">
        <h2 class="bs7-section-title">
            <i v-if="icon" class="bi" :class="icon"></i>
            {{ title }}
        </h2>
        <div class="bs7-section-actions">
            <slot />
            <Link v-if="href" :href="href" class="bs7-section-link">
                {{ linkText }} <i class="bi bi-arrow-right"></i>
            </Link>
        </div>
    </div>
</template>