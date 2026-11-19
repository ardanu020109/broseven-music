<script setup>
import { Link } from '@inertiajs/vue3';
import { playCollection } from '../Composables/usePlayCollection';

const props = defineProps({
    playlist: { type: Object, required: true },
    showActions: { type: Boolean, default: true },
});

defineEmits(['delete']);

function play() {
    playCollection(`/queue/playlists/${props.playlist.id}`);
}
</script>

<template>
    <div class="bs7-entity-card h-100">
        <div class="bs7-media-wrap">
            <Link :href="`/playlists/${playlist.id}`" class="bs7-card-link" :aria-label="`Buka playlist ${playlist.name}`">
                <div class="bs7-entity-media is-square">
                    <img v-if="playlist.cover_url" :src="playlist.cover_url" :alt="playlist.name" loading="lazy" />
                    <span v-else class="bs7-placeholder"><i class="bi bi-music-note-list"></i></span>
                </div>
            </Link>

            <button
                v-if="playlist.songs_count > 0"
                type="button"
                class="bs7-play-fab"
                :aria-label="`Putar playlist ${playlist.name}`"
                @click="play"
            >
                <i class="bi bi-play-fill"></i>
            </button>
        </div>

        <Link :href="`/playlists/${playlist.id}`" class="bs7-card-link mt-3">
            <h3 class="h6 fw-bold mb-1 text-truncate" :title="playlist.name">{{ playlist.name }}</h3>
            <div class="small text-secondary">{{ playlist.songs_count ?? 0 }} lagu</div>
        </Link>

        <div v-if="showActions" class="bs7-entity-actions">
            <Link :href="`/playlists/${playlist.id}/edit`" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i> Edit
            </Link>
            <button type="button" class="btn btn-sm btn-outline-danger" @click="$emit('delete', playlist)">
                <i class="bi bi-trash"></i> Hapus
            </button>
        </div>
    </div>
</template>