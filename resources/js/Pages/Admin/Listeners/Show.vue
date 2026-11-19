<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import SectionHeader from '../../../Components/SectionHeader.vue';
import AdminSongList from '../../../Components/AdminSongList.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { formatBytes, formatDateTime } from '../../../Composables/useFormat';
import { playCollection, playFromCollection } from '../../../Composables/usePlayCollection';
import { usePlayerStore } from '../../../Stores/player';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    listener: { type: Object, required: true },
    stats: { type: Object, required: true },
    songs: { type: Array, default: () => [] },
});

const player = usePlayerStore();

const tiles = computed(() => [
    { label: 'Lagu', value: props.stats.songs, icon: 'bi-music-note-beamed', tone: '' },
    { label: 'Album', value: props.stats.albums, icon: 'bi-disc', tone: 'is-cyan' },
    { label: 'Artis', value: props.stats.artists, icon: 'bi-mic', tone: '' },
    { label: 'Playlist', value: props.stats.playlists, icon: 'bi-music-note-list', tone: 'is-cyan' },
    { label: 'Favorit', value: props.stats.favorites, icon: 'bi-heart-fill', tone: 'is-pink' },
    { label: 'Penyimpanan', value: formatBytes(props.stats.storage), icon: 'bi-hdd', tone: '' },
]);

const ownerQuery = computed(() => ({ owner_id: props.listener.id }));

function play(song) {
    playFromCollection('/admin/queue/songs', song, { query: ownerQuery.value, fallback: props.songs });
}

function playAll() {
    playCollection('/admin/queue/songs', { query: ownerQuery.value });
}

// ---- Status dan hapus ----
const busy = ref(false);
const showDeactivate = ref(false);
const showDelete = ref(false);

function activate() {
    router.patch(`/admin/listeners/${props.listener.id}/status`, { is_active: true }, { preserveScroll: true });
}

function deactivate() {
    busy.value = true;
    router.patch(
        `/admin/listeners/${props.listener.id}/status`,
        { is_active: false },
        {
            preserveScroll: true,
            onFinish: () => {
                busy.value = false;
                showDeactivate.value = false;
            },
        },
    );
}

function destroy() {
    busy.value = true;
    router.delete(`/admin/listeners/${props.listener.id}`, {
        onFinish: () => {
            busy.value = false;
            showDelete.value = false;
        },
    });
}
</script>

<template>
    <Head :title="listener.name" />

    <div>
        <Link href="/admin/listeners" class="small text-decoration-none">
            <i class="bi bi-arrow-left"></i> Semua listener
        </Link>

        <div class="bs7-page-card mt-3 mb-4">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="bs7-avatar is-lg">{{ listener.name.charAt(0).toUpperCase() }}</span>

                <div class="min-w-0 flex-grow-1">
                    <h1 class="h4 fw-bold mb-1 text-break">{{ listener.name }}</h1>
                    <div class="text-secondary text-break">{{ listener.email }}</div>
                    <div class="mt-2 d-flex flex-wrap align-items-center gap-2">
                        <span class="bs7-status-badge" :class="listener.is_active ? 'is-active' : 'is-inactive'">
                            {{ listener.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        <span class="small text-secondary">Bergabung {{ formatDateTime(listener.created_at) }}</span>
                        <span class="small text-secondary">&middot; Login terakhir {{ formatDateTime(listener.last_login_at) }}</span>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <Link :href="`/admin/listeners/${listener.id}/edit`" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </Link>
                    <button v-if="listener.is_active" type="button" class="btn btn-outline-secondary" @click="showDeactivate = true">
                        <i class="bi bi-slash-circle me-1"></i> Nonaktifkan
                    </button>
                    <button v-else type="button" class="btn btn-outline-secondary" @click="activate">
                        <i class="bi bi-check-circle me-1"></i> Aktifkan
                    </button>
                    <button type="button" class="btn btn-outline-danger" @click="showDelete = true">
                        <i class="bi bi-trash me-1"></i> Hapus
                    </button>
                </div>
            </div>
        </div>

        <div class="bs7-stats mb-4">
            <div v-for="tile in tiles" :key="tile.label" class="bs7-stat">
                <span class="bs7-stat-icon" :class="tile.tone"><i class="bi" :class="tile.icon"></i></span>
                <span class="min-w-0">
                    <span class="bs7-stat-num d-block">{{ tile.value }}</span>
                    <span class="bs7-stat-label d-block">{{ tile.label }}</span>
                </span>
            </div>
        </div>

        <section class="bs7-page-card">
            <SectionHeader title="Lagu Terbaru" icon="bi-music-note-beamed" :href="`/admin/songs?owner_id=${listener.id}`" link-text="Lihat semua lagunya">
                <button v-if="songs.length" type="button" class="btn btn-sm btn-primary" @click="playAll">
                    <i class="bi bi-play-fill"></i> Putar
                </button>
            </SectionHeader>

            <AdminSongList
                v-if="songs.length"
                :songs="songs"
                :current-id="player.current?.id ?? null"
                :playing="player.playing"
                :show-owner="false"
                @play="play"
            />
            <div v-else class="bs7-empty py-4">
                <i class="bi bi-music-note-beamed d-block mb-2"></i>
                <p class="mb-0">Listener ini belum mengunggah lagu.</p>
            </div>
        </section>
    </div>

    <ConfirmModal
        :show="showDeactivate"
        title="Nonaktifkan listener?"
        :message="`${listener.name} tidak akan bisa login dan langsung dikeluarkan dari semua perangkat. Data koleksinya tetap tersimpan dan bisa diaktifkan kembali kapan saja.`"
        confirm-text="Nonaktifkan"
        :processing="busy"
        @confirm="deactivate"
        @cancel="showDeactivate = false"
    />

    <ConfirmModal
        :show="showDelete"
        title="Hapus listener?"
        :message="`${listener.name} akan dihapus PERMANEN beserta ${stats.songs} lagu, ${stats.albums} album, ${stats.artists} artis, ${stats.playlists} playlist, dan seluruh file miliknya (${formatBytes(stats.storage)}). Tindakan ini tidak dapat dibatalkan.`"
        :processing="busy"
        @confirm="destroy"
        @cancel="showDelete = false"
    />
</template>