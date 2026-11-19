<script setup>
import { computed, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import SingleSelect from './SingleSelect.vue';
import { readAudioDuration } from '../Composables/useAudioMeta';
import { formatDuration, formatSize } from '../Composables/useFormat';

const props = defineProps({
    song: { type: Object, default: null },
    albumOptions: { type: Array, default: () => [] },
    artistOptions: { type: Array, default: () => [] },
    maxMb: { type: Number, default: 50 },
    formats: { type: String, default: 'MP3, WAV, OGG, FLAC, M4A, AAC' },
});

const allowedExtensions = ['mp3', 'wav', 'ogg', 'oga', 'flac', 'm4a', 'aac'];

const isEdit = computed(() => !!props.song);

const form = useForm({
    ...(props.song ? { _method: 'put' } : {}),
    title: props.song?.title ?? '',
    album_id: props.song?.album_id ?? '',
    artist_id: props.song?.artist_id ?? '',
    track_number: props.song?.track_number ?? '',
    audio: null,
    duration: '',
});

const fileError = ref(null);
const detecting = ref(false);

async function onFileChange(event) {
    const input = event.target;
    const file = input.files?.[0] ?? null;

    fileError.value = null;
    form.clearErrors('audio');
    form.audio = null;
    form.duration = '';

    if (!file) return;

    const extension = file.name.split('.').pop().toLowerCase();

    if (!allowedExtensions.includes(extension)) {
        fileError.value = `Format file harus ${props.formats}.`;
        input.value = '';
        return;
    }

    if (file.size > props.maxMb * 1024 * 1024) {
        fileError.value = `Ukuran file maksimal ${props.maxMb} MB (file ini ${formatSize(file.size)}).`;
        input.value = '';
        return;
    }

    form.audio = file;

    if (!form.title) {
        form.title = file.name.replace(/\.[^.]+$/, '');
    }

    detecting.value = true;
    const seconds = await readAudioDuration(file);
    detecting.value = false;
    form.duration = seconds ?? '';
}

// Jika album dipilih dan artis masih kosong, ikuti artis milik album
watch(
    () => form.album_id,
    (id) => {
        if (!id || form.artist_id) return;

        const album = props.albumOptions.find((a) => a.id === Number(id));

        if (album?.artist_id) {
            form.artist_id = album.artist_id;
        }
    },
);

function submit() {
    if (!isEdit.value && !form.audio) {
        fileError.value = 'File musik wajib dipilih.';
        return;
    }

    const url = isEdit.value ? `/songs/${props.song.id}` : '/songs';

    form.post(url, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <form novalidate @submit.prevent="submit">
        <div class="mb-3">
            <label for="audio" class="form-label fw-semibold">
                File musik <span v-if="!isEdit" class="text-danger">*</span>
                <span v-else class="text-secondary fw-normal">(opsional, isi hanya untuk mengganti file)</span>
            </label>
            <input
                id="audio"
                type="file"
                class="form-control"
                :class="{ 'is-invalid': form.errors.audio || fileError }"
                accept=".mp3,.wav,.ogg,.oga,.flac,.m4a,.aac,audio/*"
                @change="onFileChange"
            />
            <div class="form-text">{{ formats }}. Maksimal {{ maxMb }} MB.</div>
            <div v-if="fileError" class="invalid-feedback d-block">{{ fileError }}</div>
            <div v-else-if="form.errors.audio" class="invalid-feedback d-block">{{ form.errors.audio }}</div>

            <div v-if="form.audio" class="small mt-2 text-secondary">
                <i class="bi bi-file-earmark-music me-1"></i>
                {{ form.audio.name }} &middot; {{ formatSize(form.audio.size) }}
                <template v-if="detecting"> &middot; membaca durasi...</template>
                <template v-else-if="form.duration !== ''"> &middot; durasi {{ formatDuration(form.duration) }}</template>
                <template v-else> &middot; durasi tidak terbaca</template>
            </div>

            <div v-if="isEdit && !form.audio" class="small mt-3">
                <div class="text-secondary mb-1">
                    File saat ini: {{ song.mime_type }} &middot; {{ formatSize(song.file_size) }} &middot;
                    durasi {{ formatDuration(song.duration) }}
                </div>
                <audio :src="song.stream_url" controls preload="none" class="w-100"></audio>
            </div>
        </div>

        <div class="mb-3">
            <label for="title" class="form-label fw-semibold">Judul lagu <span class="text-danger">*</span></label>
            <input
                id="title"
                v-model="form.title"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': form.errors.title }"
                maxlength="200"
            />
            <div v-if="form.errors.title" class="invalid-feedback">{{ form.errors.title }}</div>
        </div>

        <div class="row">
            <div class="col-md-5 mb-3">
                <label for="album_id" class="form-label fw-semibold">Album</label>
                <SingleSelect
                    id="album_id"
                    v-model="form.album_id"
                    :options="albumOptions"
                    placeholder="Tanpa album"
                    empty-label="Tanpa album"
                    search-placeholder="Cari album..."
                    empty-text="Belum ada album"
                    :invalid="!!form.errors.album_id"
                />
                <div v-if="form.errors.album_id" class="invalid-feedback d-block">{{ form.errors.album_id }}</div>
                <div v-if="!albumOptions.length" class="form-text">
                    Belum ada album. <Link href="/albums/create">Tambah album</Link>
                </div>
            </div>

            <div class="col-md-5 mb-3">
                <label for="artist_id" class="form-label fw-semibold">Artis</label>
                <SingleSelect
                    id="artist_id"
                    v-model="form.artist_id"
                    :options="artistOptions"
                    placeholder="Tanpa artis"
                    empty-label="Tanpa artis"
                    search-placeholder="Cari artis..."
                    empty-text="Belum ada artis"
                    :invalid="!!form.errors.artist_id"
                />
                <div v-if="form.errors.artist_id" class="invalid-feedback d-block">{{ form.errors.artist_id }}</div>
                <div v-if="!artistOptions.length" class="form-text">
                    Belum ada artis. <Link href="/artists/create">Tambah artis</Link>
                </div>
            </div>

            <div class="col-md-2 mb-3">
                <label for="track_number" class="form-label fw-semibold">No. track</label>
                <input
                    id="track_number"
                    v-model="form.track_number"
                    type="number"
                    min="1"
                    max="999"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.track_number }"
                />
                <div v-if="form.errors.track_number" class="invalid-feedback">{{ form.errors.track_number }}</div>
            </div>
        </div>

        <div v-if="form.progress" class="progress mb-3" role="progressbar" aria-label="Progres upload" style="height: 10px;">
            <div class="progress-bar bs7-progress" :style="{ width: form.progress.percentage + '%' }"></div>
        </div>

        <div class="d-flex gap-2 justify-content-end mt-4">
            <Link href="/songs" class="btn btn-outline-secondary">Batal</Link>
            <button type="submit" class="btn btn-primary" :disabled="form.processing || detecting">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEdit ? 'Simpan Perubahan' : 'Unggah Lagu' }}
            </button>
        </div>
    </form>
</template>