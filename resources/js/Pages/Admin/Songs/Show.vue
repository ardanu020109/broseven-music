<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { formatDateTime, formatDuration, formatSize } from '../../../Composables/useFormat';
import { usePlayerStore } from '../../../Stores/player';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    song: { type: Object, required: true },
});

const player = usePlayerStore();

const isCurrent = computed(() => player.current?.id === props.song.id);

function play() {
    player.playFrom([props.song], 0);
}
</script>

<template>
    <Head :title="song.title" />

    <div>
        <Link href="/admin/songs" class="small text-decoration-none">
            <i class="bi bi-arrow-left"></i> Semua musik
        </Link>

        <div class="bs7-playlist-head mt-3 mb-4">
            <div class="bs7-playlist-cover">
                <img v-if="song.cover_url" :src="song.cover_url" :alt="song.title" />
                <span v-else class="bs7-placeholder"><i class="bi bi-music-note-beamed"></i></span>
            </div>

            <div class="min-w-0 flex-grow-1">
                <div class="small text-uppercase text-secondary" style="letter-spacing: 0.08em;">Lagu</div>
                <h1 class="h2 fw-bold mb-1 text-break">{{ song.title }}</h1>
                <div class="text-secondary mb-3">
                    {{ song.artist?.name ?? 'Tanpa artis' }} &middot; {{ song.album?.name ?? 'Tanpa album' }}
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary" @click="play">
                        <i class="bi me-1" :class="isCurrent && player.playing ? 'bi-pause-fill' : 'bi-play-fill'"></i>
                        {{ isCurrent && player.playing ? 'Jeda' : 'Putar' }}
                    </button>
                    <a :href="song.download_url" class="btn btn-outline-primary" download>
                        <i class="bi bi-download me-1"></i> Download
                    </a>
                </div>
            </div>
        </div>

        <div class="bs7-page-card">
            <h2 class="h5 fw-bold mb-3">Detail lagu</h2>
            <dl class="bs7-dl mb-0">
                <dt>Pemilik</dt>
                <dd>
                    <Link v-if="song.user" :href="`/admin/listeners/${song.user.id}`">{{ song.user.name }}</Link>
                    <span v-else>-</span>
                    <span v-if="song.user" class="text-secondary small"> ({{ song.user.email }})</span>
                </dd>
                <dt>Artis</dt>
                <dd>{{ song.artist?.name ?? '-' }}</dd>
                <dt>Album</dt>
                <dd>{{ song.album?.name ?? '-' }}</dd>
                <dt>Nomor track</dt>
                <dd>{{ song.track_number ?? '-' }}</dd>
                <dt>Durasi</dt>
                <dd>{{ formatDuration(song.duration) }}</dd>
                <dt>Format</dt>
                <dd>{{ song.mime_type }}</dd>
                <dt>Ukuran file</dt>
                <dd>{{ formatSize(song.file_size) }}</dd>
                <dt>Diunggah</dt>
                <dd>{{ formatDateTime(song.created_at) }}</dd>
            </dl>

            <template v-if="song.lyrics">
                <h2 class="h5 fw-bold mt-4 mb-2">Lirik</h2>
                <div class="bs7-lyrics">{{ song.lyrics }}</div>
            </template>
        </div>
    </div>
</template>