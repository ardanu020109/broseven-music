<script setup>
import { Link } from '@inertiajs/vue3';
import { formatDate, formatDuration } from '../Composables/useFormat';

defineProps({
    songs: { type: Array, required: true },
    currentId: { type: Number, default: null },
    playing: { type: Boolean, default: false },
    showOwner: { type: Boolean, default: true },
});

defineEmits(['play']);
</script>

<template>
    <div class="bs7-song-list">
        <div
            v-for="song in songs"
            :key="song.id"
            class="bs7-song-row"
            :class="{ 'is-playing': song.id === currentId }"
        >
            <button type="button" class="bs7-song-cover" :aria-label="`Putar ${song.title}`" @click="$emit('play', song)">
                <img v-if="song.cover_url" :src="song.cover_url" alt="" loading="lazy" />
                <span v-else class="bs7-placeholder"><i class="bi bi-music-note-beamed"></i></span>
                <span class="bs7-song-playicon">
                    <i class="bi" :class="song.id === currentId && playing ? 'bi-pause-fill' : 'bi-play-fill'"></i>
                </span>
            </button>

            <div class="min-w-0">
                <Link :href="`/admin/songs/${song.id}`" class="bs7-song-title bs7-title-link text-truncate d-block" :title="song.title">
                    {{ song.title }}
                </Link>
                <div class="bs7-song-sub text-truncate">
                    {{ song.artist?.name ?? 'Tanpa artis' }}
                    <span class="mx-1">&middot;</span>
                    {{ song.album?.name ?? 'Tanpa album' }}
                </div>
                <div class="bs7-song-sub text-truncate">
                    <template v-if="showOwner && song.user">
                        <i class="bi bi-person"></i>
                        <Link :href="`/admin/listeners/${song.user.id}`" class="bs7-title-link">{{ song.user.name }}</Link>
                        <span class="mx-1">&middot;</span>
                    </template>
                    {{ formatDate(song.created_at) }}
                </div>
            </div>

            <div class="bs7-song-duration d-none d-sm-block">{{ formatDuration(song.duration) }}</div>

            <div class="bs7-song-actions">
                <a :href="song.download_url" class="bs7-icon-btn" title="Download" aria-label="Download" download>
                    <i class="bi bi-download"></i>
                </a>
                <Link :href="`/admin/songs/${song.id}`" class="bs7-icon-btn" title="Detail" aria-label="Detail">
                    <i class="bi bi-info-circle"></i>
                </Link>
            </div>
        </div>
    </div>
</template>