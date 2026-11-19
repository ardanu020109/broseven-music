<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { default: null },
    currentUrl: { type: String, default: null },
    remove: { type: Boolean, default: false },
    shape: { type: String, default: 'square' },
    label: { type: String, default: 'Gambar' },
    error: { type: String, default: null },
    placeholder: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'update:remove']);

const input = ref(null);
const previewUrl = ref(null);

function revoke() {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
}

watch(
    () => props.modelValue,
    (file) => {
        revoke();
        previewUrl.value = file ? URL.createObjectURL(file) : null;
    },
    { immediate: true },
);

onBeforeUnmount(revoke);

const shown = computed(() => previewUrl.value || (props.remove ? null : props.currentUrl));

function onChange(event) {
    const file = event.target.files?.[0] ?? null;
    emit('update:modelValue', file);

    if (file) {
        emit('update:remove', false);
    }
}

function clearSelected() {
    emit('update:modelValue', null);

    if (input.value) {
        input.value.value = '';
    }
}
</script>

<template>
    <div>
        <label class="form-label fw-semibold d-block">{{ label }}</label>

        <div class="bs7-upload-preview" :class="shape === 'circle' ? 'is-circle' : 'is-square'">
            <img v-if="shown" :src="shown" alt="Pratinjau gambar" />
            <span v-else class="bs7-placeholder">
                <template v-if="shape === 'circle' && placeholder">{{ placeholder }}</template>
                <i v-else class="bi" :class="shape === 'circle' ? 'bi-person' : 'bi-disc'"></i>
            </span>
        </div>

        <input
            ref="input"
            type="file"
            class="form-control form-control-sm mt-3"
            :class="{ 'is-invalid': error }"
            accept="image/jpeg,image/png,image/webp"
            @change="onChange"
        />
        <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
        <div v-if="error" class="invalid-feedback d-block">{{ error }}</div>

        <button v-if="modelValue" type="button" class="btn btn-link btn-sm px-0" @click="clearSelected">
            Batalkan pilihan file
        </button>

        <label v-if="currentUrl && !modelValue" class="d-flex align-items-center gap-2 small mt-2">
            <input
                type="checkbox"
                class="form-check-input mt-0"
                :checked="remove"
                @change="emit('update:remove', $event.target.checked)"
            />
            Hapus gambar saat ini
        </label>
    </div>
</template>