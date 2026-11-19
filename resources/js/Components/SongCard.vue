<script setup>
defineProps({
    song: { type: Object, required: true },
    active: { type: Boolean, default: false },
    playing: { type: Boolean, default: false },
});

defineEmits(['play', 'favorite']);
</script>

<template>
    <div class="bs7-entity-card bs7-songcard h-100" :class="{ 'is-active': active }">
        <div class="bs7-media-wrap">
            <div class="bs7-entity-media is-square">
                <img v-if="song.cover_url" :src="song.cover_url" :alt="song.title" loading="lazy" />
                <span v-else class="bs7-placeholder"><i class="bi bi-music-note-beamed"></i></span>
            </div>

            <button
                type="button"
                class="bs7-play-fab"
                :class="{ 'is-on': active }"
                :aria-label="active && playing ? `Jeda ${song.title}` : `Putar ${song.title}`"
                @click="$emit('play', song)"
            >
                <i class="bi" :class="active && playing ? 'bi-pause-fill' : 'bi-play-fill'"></i>
            </button>

            <button
                type="button"
                class="bs7-icon-btn bs7-fav bs7-fav-chip"
                :class="{ 'is-fav': song.is_favorite }"
                :aria-label="song.is_favorite ? 'Hapus dari favorit' : 'Tambah ke favorit'"
                :aria-pressed="!!song.is_favorite"
                @click="$emit('favorite', song)"
            >
                <i class="bi" :class="song.is_favorite ? 'bi-heart-fill' : 'bi-heart'"></i>
            </button>
        </div>

        <div class="mt-3 min-w-0">
            <h3 class="h6 fw-bold mb-1 text-truncate" :title="song.title">{{ song.title }}</h3>
            <div class="small text-secondary text-truncate">{{ song.artist?.name ?? 'Tanpa artis' }}</div>
        </div>
    </div>
</template>