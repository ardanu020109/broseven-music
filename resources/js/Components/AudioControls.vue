<script setup>
import { computed, ref } from 'vue';
import { usePlayerStore } from '../Stores/player';
import { formatDuration } from '../Composables/useFormat';

const player = usePlayerStore();

const scrubbing = ref(false);
const scrubValue = ref(0);

const shown = computed(() => (scrubbing.value ? scrubValue.value : player.currentTime));
const total = computed(() => player.duration);
const progress = computed(() => (total.value > 0 ? Math.min(100, (shown.value / total.value) * 100) : 0));

const repeatLabel = computed(
    () => ({ off: 'Ulangi: mati', all: 'Ulangi semua lagu', one: 'Ulangi satu lagu' })[player.repeat],
);

// Saat digeser hanya menampilkan nilai; lompat ke posisi baru dilakukan saat dilepas
function onInput(event) {
    scrubbing.value = true;
    scrubValue.value = Number(event.target.value);
}

function onChange(event) {
    player.seek(Number(event.target.value));
    scrubbing.value = false;
}
</script>

<template>
    <div class="bs7-mp-center">
        <div class="bs7-mp-controls">
            <button
                type="button"
                class="bs7-mp-btn bs7-mp-toggle"
                :class="{ 'is-active': player.shuffle }"
                :title="player.shuffle ? 'Acak: aktif' : 'Acak: mati'"
                :aria-label="player.shuffle ? 'Acak aktif' : 'Acak mati'"
                :aria-pressed="player.shuffle"
                @click="player.toggleShuffle()"
            >
                <i class="bi bi-shuffle"></i>
            </button>

            <button type="button" class="bs7-mp-btn" title="Sebelumnya" aria-label="Sebelumnya" @click="player.prev()">
                <i class="bi bi-skip-start-fill"></i>
            </button>

            <button
                type="button"
                class="bs7-mp-btn bs7-mp-play"
                :title="player.playing ? 'Jeda' : 'Putar'"
                :aria-label="player.playing ? 'Jeda' : 'Putar'"
                @click="player.toggle()"
            >
                <span v-if="player.loading && !player.error" class="spinner-border" aria-hidden="true"></span>
                <i v-else class="bi" :class="player.playing ? 'bi-pause-fill' : 'bi-play-fill'"></i>
            </button>

            <button
                type="button"
                class="bs7-mp-btn"
                title="Berikutnya"
                aria-label="Berikutnya"
                :disabled="!player.hasNext"
                @click="player.next()"
            >
                <i class="bi bi-skip-end-fill"></i>
            </button>

            <button type="button" class="bs7-mp-btn" title="Berhenti" aria-label="Berhenti" @click="player.stop()">
                <i class="bi bi-stop-fill"></i>
            </button>

            <button
                type="button"
                class="bs7-mp-btn bs7-mp-toggle"
                :class="{ 'is-active': player.repeat !== 'off' }"
                :title="repeatLabel"
                :aria-label="repeatLabel"
                @click="player.cycleRepeat()"
            >
                <i class="bi" :class="player.repeat === 'one' ? 'bi-repeat-1' : 'bi-repeat'"></i>
            </button>
        </div>

        <div class="bs7-mp-seek">
            <span class="bs7-mp-time">{{ formatDuration(shown) }}</span>
            <input
                type="range"
                class="bs7-range"
                min="0"
                :max="total || 1"
                step="0.1"
                :value="shown"
                :disabled="!total"
                :style="{ '--p': progress + '%' }"
                aria-label="Posisi lagu"
                @input="onInput"
                @change="onChange"
            />
            <span class="bs7-mp-time">{{ formatDuration(total) }}</span>
        </div>
    </div>
</template>