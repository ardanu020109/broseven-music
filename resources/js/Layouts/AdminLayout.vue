<script setup>
import { ref } from 'vue';
import AdminSidebar from '../Components/AdminSidebar.vue';
import FlashMessage from '../Components/FlashMessage.vue';
import { usePlayerStore } from '../Stores/player';

const open = ref(false);
const player = usePlayerStore();
</script>

<template>
    <div class="bs7-app">
        <AdminSidebar :open="open" @close="open = false" />
        <div v-if="open" class="bs7-backdrop" @click="open = false"></div>

        <div class="bs7-main">
            <div class="bs7-mobilebar d-lg-none">
                <button type="button" class="btn btn-link text-light fs-3 p-0 lh-1" aria-label="Buka menu" @click="open = true">
                    <i class="bi bi-list"></i>
                </button>
                <strong>BroSeven! Music</strong>
            </div>

            <FlashMessage />

            <!-- Pemutar musik dipasang di app.js (akar aplikasi), bukan di sini -->
            <main class="bs7-content" :class="{ 'has-player': player.hasTrack }">
                <slot />
            </main>
        </div>
    </div>
</template>