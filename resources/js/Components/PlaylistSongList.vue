<script setup>
import { ref } from 'vue';
import { formatDuration } from '../Composables/useFormat';

const props = defineProps({
    songs: { type: Array, required: true },
    currentId: { type: Number, default: null },
    playing: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['play', 'favorite', 'remove', 'reorder']);

const dragIndex = ref(null);
const overIndex = ref(null);

function moveTo(from, to) {
    if (props.disabled || from === to || from < 0 || to < 0 || to >= props.songs.length) return;

    const order = props.songs.map((s) => s.id);
    const [item] = order.splice(from, 1);
    order.splice(to, 0, item);

    emit('reorder', order);
}

function onDragStart(index, event) {
    if (props.disabled) {
        event.preventDefault();
        return;
    }

    dragIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(index));

    const row = event.target.closest('.bs7-song-row');

    if (row && event.dataTransfer.setDragImage) {
        event.dataTransfer.setDragImage(row, 24, 24);
    }
}

function onDragOver(index, event) {
    if (dragIndex.value === null) return;

    event.preventDefault();
    overIndex.value = index;
}

function onDrop(index, event) {
    event.preventDefault();

    const from = dragIndex.value;
    reset();

    if (from !== null) {
        moveTo(from, index);
    }
}

function reset() {
    dragIndex.value = null;
    overIndex.value = null;
}
</script>

<template>
    <div class="bs7-song-list">
        <div
            v-for="(song, index) in songs"
            :key="song.id"
            class="bs7-song-row is-reorder"
            :class="{
                'is-playing': song.id === currentId,
                'is-dragging': dragIndex === index,
                'is-over': overIndex === index && dragIndex !== index,
            }"
            @dragover="onDragOver(index, $event)"
            @drop="onDrop(index, $event)"
        >
            <span
                class="bs7-drag-handle"
                draggable="true"
                title="Seret untuk mengubah urutan"
                @dragstart="onDragStart(index, $event)"
                @dragend="reset"
            >
                <i class="bi bi-grip-vertical"></i>
            </span>

            <button type="button" class="bs7-song-cover" :aria-label="`Putar ${song.title}`" @click="emit('play', song)">
                <img v-if="song.cover_url" :src="song.cover_url" alt="" loading="lazy" />
                <span v-else class="bs7-placeholder"><i class="bi bi-music-note-beamed"></i></span>
                <span class="bs7-song-playicon">
                    <i class="bi" :class="song.id === currentId && playing ? 'bi-pause-fill' : 'bi-play-fill'"></i>
                </span>
            </button>

            <div class="min-w-0">
                <div class="bs7-song-title text-truncate" :title="song.title">
                    <span class="text-secondary me-1">{{ index + 1 }}.</span>{{ song.title }}
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
                    type="button"
                    class="bs7-icon-btn bs7-fav"
                    :class="{ 'is-fav': song.is_favorite }"
                    :title="song.is_favorite ? 'Hapus dari favorit' : 'Tambah ke favorit'"
                    :aria-label="song.is_favorite ? 'Hapus dari favorit' : 'Tambah ke favorit'"
                    :aria-pressed="!!song.is_favorite"
                    @click="emit('favorite', song)"
                >
                    <i class="bi" :class="song.is_favorite ? 'bi-heart-fill' : 'bi-heart'"></i>
                </button>
                <a :href="song.download_url" class="bs7-icon-btn" title="Download" aria-label="Download" download>
                    <i class="bi bi-download"></i>
                </a>
                <button
                    type="button"
                    class="bs7-icon-btn"
                    title="Naikkan"
                    aria-label="Naikkan urutan"
                    :disabled="disabled || index === 0"
                    @click="moveTo(index, index - 1)"
                >
                    <i class="bi bi-arrow-up"></i>
                </button>
                <button
                    type="button"
                    class="bs7-icon-btn"
                    title="Turunkan"
                    aria-label="Turunkan urutan"
                    :disabled="disabled || index === songs.length - 1"
                    @click="moveTo(index, index + 1)"
                >
                    <i class="bi bi-arrow-down"></i>
                </button>
                <button
                    type="button"
                    class="bs7-icon-btn is-danger"
                    title="Keluarkan dari playlist"
                    aria-label="Keluarkan dari playlist"
                    @click="emit('remove', song)"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </div>
</template>