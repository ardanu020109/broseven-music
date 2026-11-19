<script setup>
import { Link } from '@inertiajs/vue3';
import { formatDuration } from '../Composables/useFormat';

defineProps({
    songs: { type: Array, required: true },
    currentId: { type: Number, default: null },
    playing: { type: Boolean, default: false },
    showActions: { type: Boolean, default: true },
    showFavorite: { type: Boolean, default: true },
});

defineEmits(['play', 'delete', 'favorite']);
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
                <div class="bs7-song-title text-truncate" :title="song.title">
                    <span v-if="song.track_number" class="text-secondary me-1">{{ song.track_number }}.</span>{{ song.title }}
                </div>
                <div class="bs7-song-sub text-truncate">
                    {{ song.artist?.name ?? 'Tanpa artis' }}
                    <span class="mx-1">&middot;</span>
                    {{ song.album?.name ?? 'Tanpa album' }}
                </div>
            </div>

            <div class="bs7-song-duration d-none d-sm-block">{{ formatDuration(song.duration) }}</div>

            <div class="bs7-song-actions">
                <button
                    v-if="showFavorite"
                    type="button"
                    class="bs7-icon-btn bs7-fav"
                    :class="{ 'is-fav': song.is_favorite }"
                    :title="song.is_favorite ? 'Hapus dari favorit' : 'Tambah ke favorit'"
                    :aria-label="song.is_favorite ? 'Hapus dari favorit' : 'Tambah ke favorit'"
                    :aria-pressed="!!song.is_favorite"
                    @click="$emit('favorite', song)"
                >
                    <i class="bi" :class="song.is_favorite ? 'bi-heart-fill' : 'bi-heart'"></i>
                </button>
                <a :href="song.download_url" class="bs7-icon-btn" title="Download" aria-label="Download" download>
                    <i class="bi bi-download"></i>
                </a>
                <template v-if="showActions">
                    <Link :href="`/songs/${song.id}/edit`" class="bs7-icon-btn" title="Edit" aria-label="Edit">
                        <i class="bi bi-pencil"></i>
                    </Link>
                    <button type="button" class="bs7-icon-btn is-danger" title="Hapus" aria-label="Hapus" @click="$emit('delete', song)">
                        <i class="bi bi-trash"></i>
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>