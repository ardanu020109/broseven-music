<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import ImageUpload from './ImageUpload.vue';

const props = defineProps({
    playlist: { type: Object, default: null },
});

const isEdit = computed(() => !!props.playlist);

const form = useForm({
    ...(props.playlist ? { _method: 'put' } : {}),
    name: props.playlist?.name ?? '',
    description: props.playlist?.description ?? '',
    cover: null,
    remove_cover: false,
});

function submit() {
    const url = isEdit.value ? `/playlists/${props.playlist.id}` : '/playlists';

    form.post(url, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <form novalidate @submit.prevent="submit">
        <div class="row g-4">
            <div class="col-md-4">
                <ImageUpload
                    v-model="form.cover"
                    v-model:remove="form.remove_cover"
                    :current-url="playlist?.cover_url"
                    shape="square"
                    label="Cover playlist"
                    :error="form.errors.cover"
                />
            </div>

            <div class="col-md-8">
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Nama playlist <span class="text-danger">*</span></label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.name }"
                        maxlength="150"
                        autofocus
                    />
                    <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Deskripsi</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="5"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.description }"
                        maxlength="2000"
                    ></textarea>
                    <div v-if="form.errors.description" class="invalid-feedback">{{ form.errors.description }}</div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end mt-4">
            <Link :href="isEdit ? `/playlists/${playlist.id}` : '/playlists'" class="btn btn-outline-secondary">Batal</Link>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEdit ? 'Simpan Perubahan' : 'Buat Playlist' }}
            </button>
        </div>
    </form>
</template>