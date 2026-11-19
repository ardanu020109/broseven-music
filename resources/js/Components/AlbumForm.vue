<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import ImageUpload from './ImageUpload.vue';
import SingleSelect from './SingleSelect.vue';

const props = defineProps({
    album: { type: Object, default: null },
    artistOptions: { type: Array, default: () => [] },
});

const isEdit = computed(() => !!props.album);
const maxYear = new Date().getFullYear() + 1;

const form = useForm({
    ...(props.album ? { _method: 'put' } : {}),
    name: props.album?.name ?? '',
    artist_id: props.album?.artist_id ?? '',
    release_year: props.album?.release_year ?? '',
    description: props.album?.description ?? '',
    cover: null,
    remove_cover: false,
});

function submit() {
    const url = isEdit.value ? `/albums/${props.album.id}` : '/albums';

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
                    :current-url="album?.cover_url"
                    shape="square"
                    label="Cover album"
                    :error="form.errors.cover"
                />
            </div>

            <div class="col-md-8">
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Nama album <span class="text-danger">*</span></label>
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

                <div class="row">
                    <div class="col-sm-8 mb-3">
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
                            Belum ada artis. <Link href="/artists/create">Tambah artis dulu</Link> agar bisa dipilih.
                        </div>
                    </div>

                    <div class="col-sm-4 mb-3">
                        <label for="release_year" class="form-label fw-semibold">Tahun rilis</label>
                        <input
                            id="release_year"
                            v-model="form.release_year"
                            type="number"
                            min="1900"
                            :max="maxYear"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.release_year }"
                        />
                        <div v-if="form.errors.release_year" class="invalid-feedback">{{ form.errors.release_year }}</div>
                    </div>
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
            <Link href="/albums" class="btn btn-outline-secondary">Batal</Link>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEdit ? 'Simpan Perubahan' : 'Simpan Album' }}
            </button>
        </div>
    </form>
</template>