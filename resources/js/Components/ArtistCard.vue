<script setup>
import { Link } from '@inertiajs/vue3';
import { playCollection } from '../Composables/usePlayCollection';

const props = defineProps({
    artist: { type: Object, required: true },
    showActions: { type: Boolean, default: true },
});

defineEmits(['delete']);

function play() {
    playCollection(`/queue/artists/${props.artist.id}`);
}
</script>

<template>
    <div class="bs7-entity-card h-100">
        <div class="bs7-media-wrap is-circle">
            <Link :href="`/artists/${artist.id}`" class="bs7-card-link" :aria-label="`Buka artis ${artist.name}`">
                <div class="bs7-entity-media is-circle">
                    <img v-if="artist.photo_url" :src="artist.photo_url" :alt="artist.name" loading="lazy" />
                    <span v-else class="bs7-placeholder">{{ artist.name.charAt(0).toUpperCase() }}</span>
                </div>
            </Link>

            <button
                v-if="artist.songs_count > 0"
                type="button"
                class="bs7-play-fab"
                :aria-label="`Putar lagu ${artist.name}`"
                @click="play"
            >
                <i class="bi bi-play-fill"></i>
            </button>
        </div>

        <Link :href="`/artists/${artist.id}`" class="bs7-card-link text-center mt-3">
            <h3 class="h6 fw-bold mb-1 text-truncate" :title="artist.name">{{ artist.name }}</h3>
            <div class="small text-secondary">
                {{ artist.albums_count ?? 0 }} album &middot; {{ artist.songs_count ?? 0 }} lagu
            </div>
        </Link>

        <div v-if="showActions" class="bs7-entity-actions">
            <Link :href="`/artists/${artist.id}/edit`" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i> Edit
            </Link>
            <button type="button" class="btn btn-sm btn-outline-danger" @click="$emit('delete', artist)">
                <i class="bi bi-trash"></i> Hapus
            </button>
        </div>
    </div>
</template>