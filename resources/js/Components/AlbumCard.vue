<script setup>
import { Link } from '@inertiajs/vue3';
import { playCollection } from '../Composables/usePlayCollection';

const props = defineProps({
    album: { type: Object, required: true },
    showActions: { type: Boolean, default: true },
});

defineEmits(['delete']);

function play() {
    playCollection(`/queue/albums/${props.album.id}`);
}
</script>

<template>
    <div class="bs7-entity-card h-100">
        <div class="bs7-media-wrap">
            <Link :href="`/albums/${album.id}`" class="bs7-card-link" :aria-label="`Buka album ${album.name}`">
                <div class="bs7-entity-media is-square">
                    <img v-if="album.cover_url" :src="album.cover_url" :alt="album.name" loading="lazy" />
                    <span v-else class="bs7-placeholder"><i class="bi bi-disc"></i></span>
                </div>
            </Link>

            <button
                v-if="album.songs_count > 0"
                type="button"
                class="bs7-play-fab"
                :aria-label="`Putar album ${album.name}`"
                @click="play"
            >
                <i class="bi bi-play-fill"></i>
            </button>
        </div>

        <Link :href="`/albums/${album.id}`" class="bs7-card-link mt-3">
            <h3 class="h6 fw-bold mb-1 text-truncate" :title="album.name">{{ album.name }}</h3>
            <div class="small text-secondary text-truncate">{{ album.artist?.name ?? 'Tanpa artis' }}</div>
            <div class="small text-secondary">
                {{ album.release_year ?? '-' }} &middot; {{ album.songs_count ?? 0 }} lagu
            </div>
        </Link>

        <div v-if="showActions" class="bs7-entity-actions">
            <Link :href="`/albums/${album.id}/edit`" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i> Edit
            </Link>
            <button type="button" class="btn btn-sm btn-outline-danger" @click="$emit('delete', album)">
                <i class="bi bi-trash"></i> Hapus
            </button>
        </div>
    </div>
</template>