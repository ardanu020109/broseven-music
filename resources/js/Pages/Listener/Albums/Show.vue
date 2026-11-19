<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import SongList from '../../../Components/SongList.vue';
import { toggleFavorite } from '../../../Composables/useFavorite';
import { formatDuration } from '../../../Composables/useFormat';
import { usePlayerStore } from '../../../Stores/player';

defineOptions({ layout: ListenerLayout });

const props = defineProps({
    album: { type: Object, required: true },
    songs: { type: Array, default: () => [] },
});

const player = usePlayerStore();

const totalDuration = computed(() => props.songs.reduce((sum, song) => sum + (Number(song.duration) || 0), 0));

function play(song) {
    player.playFrom(props.songs, props.songs.findIndex((s) => s.id === song.id));
}

function playAll() {
    player.playQueue(props.songs);
}

function playShuffled() {
    player.playShuffled(props.songs);
}
</script>

<template>
    <Head :title="album.name" />

    <div>
        <Link href="/albums" class="small text-decoration-none">
            <i class="bi bi-arrow-left"></i> Semua album
        </Link>

        <div class="bs7-playlist-head mt-3 mb-4">
            <div class="bs7-playlist-cover">
                <img v-if="album.cover_url" :src="album.cover_url" :alt="album.name" />
                <span v-else class="bs7-placeholder"><i class="bi bi-disc"></i></span>
            </div>

            <div class="min-w-0 flex-grow-1">
                <div class="small text-uppercase text-secondary" style="letter-spacing: 0.08em;">Album</div>
                <h1 class="h2 fw-bold mb-1 text-break">{{ album.name }}</h1>
                <div class="mb-1">
                    <Link v-if="album.artist" :href="`/artists/${album.artist.id}`" class="fw-semibold text-decoration-none">
                        {{ album.artist.name }}
                    </Link>
                    <span v-else class="text-secondary">Tanpa artis</span>
                    <span v-if="album.release_year" class="text-secondary"> &middot; {{ album.release_year }}</span>
                </div>
                <p v-if="album.description" class="text-secondary mb-2 text-break">{{ album.description }}</p>
                <div class="small text-secondary mb-3">
                    {{ songs.length }} lagu
                    <template v-if="songs.length"> &middot; {{ formatDuration(totalDuration) }}</template>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary" :disabled="!songs.length" @click="playAll">
                        <i class="bi bi-play-fill me-1"></i> Putar Album
                    </button>
                    <button type="button" class="btn btn-outline-primary" :disabled="!songs.length" @click="playShuffled">
                        <i class="bi bi-shuffle me-1"></i> Acak
                    </button>
                    <Link :href="`/albums/${album.id}/edit`" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </Link>
                </div>
            </div>
        </div>

        <SongList
            v-if="songs.length"
            :songs="songs"
            :current-id="player.current?.id ?? null"
            :playing="player.playing"
            :show-actions="false"
            @play="play"
            @favorite="toggleFavorite($event, ['songs'])"
        />

        <div v-else class="bs7-page-card bs7-empty">
            <i class="bi bi-disc d-block mb-3"></i>
            <h2 class="h5">Album ini belum punya lagu</h2>
            <p>Pilih album ini saat mengunggah atau mengedit lagu.</p>
            <Link href="/songs/create" class="btn btn-primary">Unggah Lagu</Link>
        </div>
    </div>
</template>