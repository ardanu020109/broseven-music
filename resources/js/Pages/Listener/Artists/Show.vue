<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ListenerLayout from '../../../Layouts/ListenerLayout.vue';
import SectionHeader from '../../../Components/SectionHeader.vue';
import AlbumCard from '../../../Components/AlbumCard.vue';
import SongList from '../../../Components/SongList.vue';
import { toggleFavorite } from '../../../Composables/useFavorite';
import { usePlayerStore } from '../../../Stores/player';

defineOptions({ layout: ListenerLayout });

const props = defineProps({
    artist: { type: Object, required: true },
    albums: { type: Array, default: () => [] },
    songs: { type: Array, default: () => [] },
});

const player = usePlayerStore();

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
    <Head :title="artist.name" />

    <div>
        <Link href="/artists" class="small text-decoration-none">
            <i class="bi bi-arrow-left"></i> Semua artis
        </Link>

        <div class="bs7-playlist-head mt-3 mb-4">
            <div class="bs7-playlist-cover is-circle">
                <img v-if="artist.photo_url" :src="artist.photo_url" :alt="artist.name" />
                <span v-else class="bs7-placeholder">{{ artist.name.charAt(0).toUpperCase() }}</span>
            </div>

            <div class="min-w-0 flex-grow-1">
                <div class="small text-uppercase text-secondary" style="letter-spacing: 0.08em;">Artis</div>
                <h1 class="h2 fw-bold mb-1 text-break">{{ artist.name }}</h1>
                <p v-if="artist.biography" class="text-secondary mb-2 text-break">{{ artist.biography }}</p>
                <div class="small text-secondary mb-3">{{ albums.length }} album &middot; {{ songs.length }} lagu</div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary" :disabled="!songs.length" @click="playAll">
                        <i class="bi bi-play-fill me-1"></i> Putar Semua
                    </button>
                    <button type="button" class="btn btn-outline-primary" :disabled="!songs.length" @click="playShuffled">
                        <i class="bi bi-shuffle me-1"></i> Acak
                    </button>
                    <Link :href="`/artists/${artist.id}/edit`" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </Link>
                </div>
            </div>
        </div>

        <section v-if="albums.length" class="bs7-section">
            <SectionHeader title="Album" icon="bi-disc" />
            <div class="bs7-hscroll">
                <div v-for="album in albums" :key="album.id" class="bs7-hitem">
                    <AlbumCard :album="album" :show-actions="false" />
                </div>
            </div>
        </section>

        <section class="bs7-section">
            <SectionHeader title="Lagu" icon="bi-music-note-beamed" />

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
                <i class="bi bi-mic d-block mb-3"></i>
                <h2 class="h5">Artis ini belum punya lagu</h2>
                <p class="mb-0">Pilih artis ini saat mengunggah atau mengedit lagu.</p>
            </div>
        </section>
    </div>
</template>