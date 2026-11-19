<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import SectionHeader from '../../Components/SectionHeader.vue';
import AdminSongList from '../../Components/AdminSongList.vue';
import { formatBytes, formatDate } from '../../Composables/useFormat';
import { usePlayerStore } from '../../Stores/player';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    stats: { type: Object, required: true },
    recentListeners: { type: Array, default: () => [] },
    recentSongs: { type: Array, default: () => [] },
});

const page = usePage();
const player = usePlayerStore();
const user = computed(() => page.props.auth.user);

const tiles = computed(() => [
    {
        label: 'Total Listener',
        value: props.stats.listeners,
        sub: `${props.stats.active_listeners} aktif / ${props.stats.inactive_listeners} nonaktif`,
        icon: 'bi-people',
        tone: '',
        href: '/admin/listeners',
    },
    { label: 'Total Musik', value: props.stats.songs, sub: 'semua listener', icon: 'bi-music-note-beamed', tone: 'is-cyan', href: '/admin/songs' },
    { label: 'Total Album', value: props.stats.albums, sub: '', icon: 'bi-disc', tone: '', href: '' },
    { label: 'Total Artis', value: props.stats.artists, sub: '', icon: 'bi-mic', tone: 'is-cyan', href: '' },
    { label: 'Total Playlist', value: props.stats.playlists, sub: '', icon: 'bi-music-note-list', tone: 'is-pink', href: '' },
    { label: 'Penyimpanan', value: formatBytes(props.stats.storage), sub: 'file musik', icon: 'bi-hdd', tone: '', href: '' },
]);

function play(song) {
    player.playFrom(props.recentSongs, props.recentSongs.findIndex((s) => s.id === song.id));
}
</script>

<template>
    <Head title="Dashboard Superadmin" />

    <div>
        <div class="mb-4">
            <div class="small text-uppercase text-secondary" style="letter-spacing: 0.08em;">Dashboard</div>
            <h1 class="h3 fw-bold mb-0">Halo, {{ user.name }}</h1>
            <div class="text-secondary">Ringkasan seluruh koleksi musik di BroSeven!.</div>
        </div>

        <div class="bs7-stats mb-4">
            <component
                :is="tile.href ? Link : 'div'"
                v-for="tile in tiles"
                :key="tile.label"
                :href="tile.href || undefined"
                class="bs7-stat"
            >
                <span class="bs7-stat-icon" :class="tile.tone"><i class="bi" :class="tile.icon"></i></span>
                <span class="min-w-0">
                    <span class="bs7-stat-num d-block" style="font-size: 14px;">{{ tile.value }}</span>
                    <span class="bs7-stat-label d-block" style="font-size: 10px;">{{ tile.label }}</span>
                    <span v-if="tile.sub" class="bs7-stat-sub d-block" style="font-size: 10px;">{{ tile.sub }}</span>
                </span>
            </component>
        </div>

        <div class="row g-4">
            <div class="col-xl-5">
                <section class="bs7-page-card h-100">
                    <SectionHeader title="Listener Terbaru" icon="bi-people" href="/admin/listeners" />

                    <div v-if="recentListeners.length" class="bs7-mini-list">
                        <Link v-for="item in recentListeners" :key="item.id" :href="`/admin/listeners/${item.id}`" class="bs7-mini-row">
                            <span class="bs7-avatar">{{ item.name.charAt(0).toUpperCase() }}</span>
                            <span class="min-w-0 flex-grow-1">
                                <span class="d-block fw-semibold text-truncate">{{ item.name }}</span>
                                <span class="d-block small text-secondary text-truncate">{{ item.email }}</span>
                            </span>
                            <span class="text-end flex-none">
                                <span class="bs7-status-badge" :class="item.is_active ? 'is-active' : 'is-inactive'">
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                <span class="d-block small text-secondary mt-1">{{ item.songs_count }} lagu &middot; {{ formatDate(item.created_at) }}</span>
                            </span>
                        </Link>
                    </div>
                    <div v-else class="bs7-empty py-4">
                        <i class="bi bi-people d-block mb-2"></i>
                        <p class="mb-3">Belum ada listener.</p>
                        <Link href="/admin/listeners/create" class="btn btn-primary btn-sm">Tambah Listener</Link>
                    </div>
                </section>
            </div>

            <div class="col-xl-7">
                <section class="bs7-page-card h-100">
                    <SectionHeader title="Upload Musik Terbaru" icon="bi-cloud-arrow-up" href="/admin/songs" />

                    <AdminSongList
                        v-if="recentSongs.length"
                        :songs="recentSongs"
                        :current-id="player.current?.id ?? null"
                        :playing="player.playing"
                        @play="play"
                    />
                    <div v-else class="bs7-empty py-4">
                        <i class="bi bi-music-note-beamed d-block mb-2"></i>
                        <p class="mb-0">Belum ada musik yang diunggah.</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
