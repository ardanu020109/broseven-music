<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ListenerLayout from '../../Layouts/ListenerLayout.vue';
import SectionHeader from '../../Components/SectionHeader.vue';
import SongCard from '../../Components/SongCard.vue';
import SongList from '../../Components/SongList.vue';
import AlbumCard from '../../Components/AlbumCard.vue';
import ArtistCard from '../../Components/ArtistCard.vue';
import PlaylistCard from '../../Components/PlaylistCard.vue';
import { debounce } from '../../Composables/useDebounce';
import { toggleFavorite } from '../../Composables/useFavorite';
import { playCollection, playFromCollection } from '../../Composables/usePlayCollection';
import { usePlayerStore } from '../../Stores/player';

defineOptions({ layout: ListenerLayout });

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    sections: { type: Object, default: null },
    results: { type: Object, default: null },
});

const page = usePage();
const player = usePlayerStore();
const user = computed(() => page.props.auth.user);

const hour = new Date().getHours();
const greeting = hour < 11 ? 'Selamat pagi' : hour < 15 ? 'Selamat siang' : hour < 18 ? 'Selamat sore' : 'Selamat malam';

// ---- Pencarian ----
const search = ref(props.filters.search ?? '');
const searching = computed(() => (props.filters.search ?? '') !== '');

const load = debounce((value) => {
    router.get(
        '/home',
        { search: value.trim() || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 350);

watch(search, (value) => load(value));

const resultTotal = computed(() => {
    const r = props.results;

    return r ? r.songs.length + r.albums.length + r.artists.length + r.playlists.length : 0;
});

const isEmptyLibrary = computed(
    () =>
        !searching.value &&
        (props.stats.songs ?? 0) + (props.stats.albums ?? 0) + (props.stats.artists ?? 0) + (props.stats.playlists ?? 0) === 0,
);

const tiles = computed(() => [
    { label: 'Lagu', value: props.stats.songs ?? 0, icon: 'bi-music-note-beamed', href: '/songs', tone: '' },
    { label: 'Album', value: props.stats.albums ?? 0, icon: 'bi-disc', href: '/albums', tone: 'is-cyan' },
    { label: 'Artis', value: props.stats.artists ?? 0, icon: 'bi-mic', href: '/artists', tone: '' },
    { label: 'Playlist', value: props.stats.playlists ?? 0, icon: 'bi-music-note-list', href: '/playlists', tone: 'is-cyan' },
    { label: 'Favorit', value: props.stats.favorites ?? 0, icon: 'bi-heart-fill', href: '/favorites', tone: 'is-pink' },
]);

// ---- Pemutar ----
const currentId = computed(() => player.current?.id ?? null);
const playing = computed(() => player.playing);

function playLocal(list, song) {
    player.playFrom(list, list.findIndex((s) => s.id === song.id));
}

function fav(song) {
    toggleFavorite(song, ['sections', 'results']);
}
</script>

<template>
    <Head title="Beranda" />

    <div>
        <section class="bs7-home-hero">
            <div class="min-w-0">
                <div class="bs7-home-kicker">{{ greeting }}</div>
                <h1 class="bs7-home-name">{{ user.name }}</h1>
                <p class="bs7-home-sub">Apa yang ingin Anda dengarkan hari ini?</p>
            </div>

            <div class="bs7-home-search">
                <i class="bi bi-search"></i>
                <input
                    v-model="search"
                    type="search"
                    maxlength="100"
                    placeholder="Cari lagu, album, artis, atau playlist..."
                    aria-label="Cari di koleksi Anda"
                />
                <button v-if="search" type="button" class="bs7-home-clear" aria-label="Hapus pencarian" @click="search = ''">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </section>

        <div v-if="!searching" class="bs7-stats">
            <Link v-for="tile in tiles" :key="tile.label" :href="tile.href" class="bs7-stat">
                <span class="bs7-stat-icon" :class="tile.tone"><i class="bi" :class="tile.icon"></i></span>
                <span>
                    <span class="bs7-stat-num d-block">{{ tile.value }}</span>
                    <span class="bs7-stat-label">{{ tile.label }}</span>
                </span>
            </Link>
        </div>

        <!-- Koleksi kosong -->
        <div v-if="isEmptyLibrary" class="bs7-page-card bs7-empty">
            <i class="bi bi-vinyl d-block mb-3"></i>
            <h2 class="h4">Koleksi Anda masih kosong</h2>
            <p>Mulai dengan mengunggah lagu pertama. Anda juga bisa menambahkan artis dan album agar koleksi lebih rapi.</p>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <Link href="/songs/create" class="btn btn-primary"><i class="bi bi-cloud-arrow-up me-1"></i> Unggah Lagu</Link>
                <Link href="/artists/create" class="btn btn-outline-primary"><i class="bi bi-mic me-1"></i> Tambah Artis</Link>
                <Link href="/albums/create" class="btn btn-outline-primary"><i class="bi bi-disc me-1"></i> Tambah Album</Link>
            </div>
        </div>

        <!-- Hasil pencarian -->
        <template v-else-if="searching && results">
            <div class="d-flex flex-wrap align-items-baseline gap-2 mb-4">
                <h2 class="h5 fw-bold mb-0">Hasil untuk "{{ filters.search }}"</h2>
                <span class="text-secondary small">{{ resultTotal }} hasil</span>
            </div>

            <div v-if="!resultTotal" class="bs7-page-card bs7-empty">
                <i class="bi bi-search d-block mb-3"></i>
                <h2 class="h5">Tidak ada hasil</h2>
                <p class="mb-0">Coba kata kunci lain, misalnya sebagian judul lagu atau nama artis.</p>
            </div>

            <section v-if="results.songs.length" class="bs7-section">
                <SectionHeader title="Lagu" icon="bi-music-note-beamed" />
                <SongList
                    :songs="results.songs"
                    :current-id="currentId"
                    :playing="playing"
                    :show-actions="false"
                    @play="playLocal(results.songs, $event)"
                    @favorite="fav"
                />
            </section>

            <section v-if="results.albums.length" class="bs7-section">
                <SectionHeader title="Album" icon="bi-disc" />
                <div class="bs7-hscroll">
                    <div v-for="album in results.albums" :key="album.id" class="bs7-hitem">
                        <AlbumCard :album="album" :show-actions="false" />
                    </div>
                </div>
            </section>

            <section v-if="results.artists.length" class="bs7-section">
                <SectionHeader title="Artis" icon="bi-mic" />
                <div class="bs7-hscroll">
                    <div v-for="artist in results.artists" :key="artist.id" class="bs7-hitem">
                        <ArtistCard :artist="artist" :show-actions="false" />
                    </div>
                </div>
            </section>

            <section v-if="results.playlists.length" class="bs7-section">
                <SectionHeader title="Playlist" icon="bi-music-note-list" />
                <div class="bs7-hscroll">
                    <div v-for="playlist in results.playlists" :key="playlist.id" class="bs7-hitem">
                        <PlaylistCard :playlist="playlist" :show-actions="false" />
                    </div>
                </div>
            </section>
        </template>

        <!-- Bagian beranda -->
        <template v-else-if="sections">
            <section v-if="sections.recentlyAdded.length" class="bs7-section">
                <SectionHeader title="Recently Added" icon="bi-stars" href="/songs" />
                <div class="bs7-hscroll">
                    <div v-for="song in sections.recentlyAdded" :key="song.id" class="bs7-hitem">
                        <SongCard
                            :song="song"
                            :active="song.id === currentId"
                            :playing="playing"
                            @play="playLocal(sections.recentlyAdded, $event)"
                            @favorite="fav"
                        />
                    </div>
                </div>
            </section>

            <section v-if="sections.recentlyPlayed.length" class="bs7-section">
                <SectionHeader title="Recently Played" icon="bi-clock-history" />
                <div class="bs7-hscroll">
                    <div v-for="song in sections.recentlyPlayed" :key="song.id" class="bs7-hitem">
                        <SongCard
                            :song="song"
                            :active="song.id === currentId"
                            :playing="playing"
                            @play="playLocal(sections.recentlyPlayed, $event)"
                            @favorite="fav"
                        />
                    </div>
                </div>
            </section>

            <section v-if="sections.albums.length" class="bs7-section">
                <SectionHeader title="Album Collection" icon="bi-disc" href="/albums" />

                <div class="bs7-hscroll">
                    <div
                        v-for="album in sections.albums"
                        :key="album.id"
                        class="bs7-hitem"
                    >
                        <AlbumCard
                            :album="album"
                            :show-actions="false"
                        />
                    </div>
                </div>
            </section>

            <section v-if="sections.artists.length" class="bs7-section">
                <SectionHeader title="Artist Collection" icon="bi-mic" href="/artists" />
                <div class="bs7-hscroll">
                    <div v-for="artist in sections.artists" :key="artist.id" class="bs7-hitem">
                        <ArtistCard :artist="artist" :show-actions="false" />
                    </div>
                </div>
            </section>

            <section v-if="sections.playlists.length" class="bs7-section">
                <SectionHeader title="Playlist Collection" icon="bi-music-note-list" href="/playlists" />
                <div class="bs7-hscroll">
                    <div v-for="playlist in sections.playlists" :key="playlist.id" class="bs7-hitem">
                        <PlaylistCard :playlist="playlist" :show-actions="false" />
                    </div>
                </div>
            </section>

            <section v-if="sections.favorites.length" class="bs7-section">
                <SectionHeader title="Favorite Songs" icon="bi-heart-fill" href="/favorites">
                    <button type="button" class="btn btn-sm btn-primary" @click="playCollection('/queue/favorites')">
                        <i class="bi bi-play-fill"></i> Putar
                    </button>
                </SectionHeader>
                <SongList
                    :songs="sections.favorites"
                    :current-id="currentId"
                    :playing="playing"
                    :show-actions="false"
                    @play="playFromCollection('/queue/favorites', $event, { fallback: sections.favorites })"
                    @favorite="fav"
                />
            </section>

            <section v-if="sections.allSongs.length" class="bs7-section">
                <SectionHeader title="All Songs" icon="bi-music-note-beamed" href="/songs">
                    <button type="button" class="btn btn-sm btn-primary" @click="playCollection('/queue/songs')">
                        <i class="bi bi-play-fill"></i> Putar Semua
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="playCollection('/queue/songs', { shuffle: true })">
                        <i class="bi bi-shuffle"></i> Acak
                    </button>
                </SectionHeader>
                <SongList
                    :songs="sections.allSongs"
                    :current-id="currentId"
                    :playing="playing"
                    :show-actions="false"
                    @play="playFromCollection('/queue/songs', $event, { fallback: sections.allSongs })"
                    @favorite="fav"
                />
            </section>
        </template>
    </div>
</template>
