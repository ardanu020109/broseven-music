<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePlayerStore } from '../Stores/player';
import { formatDuration } from '../Composables/useFormat';
import VinylRecord from './VinylRecord.vue';
import AudioControls from './AudioControls.vue';

const player = usePlayerStore();

const root = ref(null);
const queueEl = ref(null);
const panelOpen = ref(false);
const activeTab = ref('now'); // now | queue

const song = computed(() => player.current);
const volumeFill = computed(() => (player.muted ? 0 : Math.round(player.volume * 100)));
const volumeIcon = computed(() => {
    if (player.muted || player.volume === 0) return 'bi-volume-mute-fill';

    return player.volume < 0.5 ? 'bi-volume-down-fill' : 'bi-volume-up-fill';
});
const repeatText = computed(() => ({ all: 'Ulangi semua', one: 'Ulangi satu lagu' })[player.repeat] ?? null);

function openPanel(target) {
    if (panelOpen.value && activeTab.value === target) {
        panelOpen.value = false;

        return;
    }

    activeTab.value = target;
    panelOpen.value = true;
}

async function scrollQueueToCurrent() {
    await nextTick();

    const list = queueEl.value;
    const el = list?.querySelector('.is-current');

    if (list && el) {
        list.scrollTop = el.offsetTop - list.clientHeight / 2 + el.clientHeight / 2;
    }
}

watch([panelOpen, activeTab], ([open, tab]) => {
    if (open && tab === 'queue') scrollQueueToCurrent();
});

watch(
    () => player.current?.id,
    () => {
        if (panelOpen.value && activeTab.value === 'queue') scrollQueueToCurrent();
    },
);

watch(
    () => player.hasTrack,
    (value) => {
        if (!value) panelOpen.value = false;
    },
);

function onDocumentMouseDown(event) {
    if (panelOpen.value && root.value && !root.value.contains(event.target)) {
        panelOpen.value = false;
    }
}

function onKeydown(event) {
    if (event.key === 'Escape' && panelOpen.value) {
        panelOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('mousedown', onDocumentMouseDown);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocumentMouseDown);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div ref="root" class="bs7-gp">
        <Transition name="bs7-mp">
            <div v-if="player.hasTrack" class="bs7-mp" role="region" aria-label="Pemutar musik">
                <div class="bs7-mp-info">
                    <button
                        type="button"
                        class="bs7-gp-open"
                        :aria-expanded="panelOpen"
                        aria-label="Buka detail lagu yang diputar"
                        @click="openPanel('now')"
                    >
                        <VinylRecord :cover="song?.cover_url" :spinning="player.playing" size="sm" />
                        <span class="min-w-0">
                            <span class="bs7-mp-title d-block text-truncate">{{ song?.title }}</span>
                            <span class="bs7-mp-sub d-block text-truncate">
                                <span v-if="player.error" class="bs7-mp-error">Gagal memuat audio</span>
                                <template v-else>{{ song?.artist ?? 'Tanpa artis' }}</template>
                            </span>
                        </span>
                    </button>

                    <span class="bs7-eq" :class="{ 'is-playing': player.playing }" aria-hidden="true">
                        <i></i><i></i><i></i><i></i>
                    </span>
                </div>

                <AudioControls />

                <div class="bs7-mp-side">
                    <div class="bs7-mp-vol">
                        <button
                            type="button"
                            class="bs7-mp-btn"
                            :title="player.muted ? 'Aktifkan suara' : 'Bisukan'"
                            aria-label="Bisukan atau aktifkan suara"
                            @click="player.toggleMute()"
                        >
                            <i class="bi" :class="volumeIcon"></i>
                        </button>
                        <input
                            type="range"
                            class="bs7-range"
                            min="0"
                            max="1"
                            step="0.01"
                            :value="player.muted ? 0 : player.volume"
                            :style="{ '--p': volumeFill + '%' }"
                            aria-label="Volume"
                            @input="player.setVolume(Number($event.target.value))"
                        />
                    </div>

                    <button
                        type="button"
                        class="bs7-mp-btn bs7-mp-toggle"
                        :class="{ 'is-active': panelOpen && activeTab === 'queue' }"
                        title="Antrean"
                        aria-label="Buka antrean"
                        @click="openPanel('queue')"
                    >
                        <i class="bi bi-music-note-list"></i>
                    </button>

                    <button
                        type="button"
                        class="bs7-mp-btn bs7-mp-close"
                        title="Tutup pemutar"
                        aria-label="Tutup pemutar"
                        @click="player.close()"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </Transition>

        <Transition name="bs7-np">
            <section v-if="player.hasTrack && panelOpen" class="bs7-np" role="dialog" aria-label="Sedang diputar dan antrean">
                <div class="bs7-np-tabs">
                    <button
                        type="button"
                        class="bs7-np-tab"
                        :class="{ 'is-active': activeTab === 'now' }"
                        @click="activeTab = 'now'"
                    >
                        Sedang Diputar
                    </button>
                    <button
                        type="button"
                        class="bs7-np-tab"
                        :class="{ 'is-active': activeTab === 'queue' }"
                        @click="activeTab = 'queue'"
                    >
                        Antrean <span class="bs7-np-count">{{ player.queue.length }}</span>
                    </button>
                    <button
                        type="button"
                        class="bs7-mp-btn bs7-np-close"
                        title="Tutup panel"
                        aria-label="Tutup panel"
                        @click="panelOpen = false"
                    >
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>

                <div class="bs7-np-body">
                    <div v-if="activeTab === 'now'" class="bs7-np-now">
                        <VinylRecord :cover="song?.cover_url" :spinning="player.playing" size="lg" />
                        <h2 class="bs7-np-title">{{ song?.title }}</h2>
                        <div class="bs7-np-artist">{{ song?.artist ?? 'Tanpa artis' }}</div>
                        <div class="bs7-np-album">{{ song?.album ?? 'Tanpa album' }}</div>

                        <div class="bs7-np-chips">
                            <span class="bs7-np-chip">{{ player.queue.length }} lagu di antrean</span>
                            <span v-if="player.shuffle" class="bs7-np-chip">Acak</span>
                            <span v-if="repeatText" class="bs7-np-chip">{{ repeatText }}</span>
                        </div>
                    </div>

                    <ol v-else ref="queueEl" class="bs7-np-queue">
                        <li v-for="item in player.playOrder" :key="`${item.pos}-${item.song.id}`">
                            <button
                                type="button"
                                class="bs7-np-qitem"
                                :class="{ 'is-current': item.pos === player.pos }"
                                @click="player.playPosition(item.pos)"
                            >
                                <span class="bs7-np-qnum">
                                    <span v-if="item.pos === player.pos" class="bs7-eq" :class="{ 'is-playing': player.playing }" aria-hidden="true">
                                        <i></i><i></i><i></i><i></i>
                                    </span>
                                    <template v-else>{{ item.pos + 1 }}</template>
                                </span>
                                <span class="bs7-np-qcover">
                                    <img v-if="item.song.cover_url" :src="item.song.cover_url" alt="" loading="lazy" />
                                    <span v-else class="bs7-placeholder" style="font-size: 1rem;"><i class="bi bi-music-note-beamed"></i></span>
                                </span>
                                <span class="min-w-0">
                                    <span class="bs7-np-qtitle d-block text-truncate">{{ item.song.title }}</span>
                                    <span class="bs7-np-qsub d-block text-truncate">{{ item.song.artist ?? 'Tanpa artis' }}</span>
                                </span>
                                <span class="bs7-np-qdur">{{ item.song.duration ? formatDuration(item.song.duration) : '-' }}</span>
                            </button>
                        </li>
                    </ol>
                </div>
            </section>
        </Transition>
    </div>
</template>