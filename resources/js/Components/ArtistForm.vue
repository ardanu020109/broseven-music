<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import ImageUpload from './ImageUpload.vue';

const props = defineProps({
    artist: { type: Object, default: null },
});

const isEdit = computed(() => !!props.artist);

const form = useForm({
    ...(props.artist ? { _method: 'put' } : {}),
    name: props.artist?.name ?? '',
    biography: props.artist?.biography ?? '',
    photo: null,
    remove_photo: false,
});

function submit() {
    const url = isEdit.value ? `/artists/${props.artist.id}` : '/artists';

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
                    v-model="form.photo"
                    v-model:remove="form.remove_photo"
                    :current-url="artist?.photo_url"
                    shape="circle"
                    label="Foto artis"
                    :error="form.errors.photo"
                    :placeholder="form.name.charAt(0).toUpperCase()"
                />
            </div>

            <div class="col-md-8">
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Nama artis <span class="text-danger">*</span></label>
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
                    <label for="biography" class="form-label fw-semibold">Biografi</label>
                    <textarea
                        id="biography"
                        v-model="form.biography"
                        rows="6"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.biography }"
                        maxlength="2000"
                    ></textarea>
                    <div v-if="form.errors.biography" class="invalid-feedback">{{ form.errors.biography }}</div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end mt-4">
            <Link href="/artists" class="btn btn-outline-secondary">Batal</Link>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEdit ? 'Simpan Perubahan' : 'Simpan Artis' }}
            </button>
        </div>
    </form>
</template>