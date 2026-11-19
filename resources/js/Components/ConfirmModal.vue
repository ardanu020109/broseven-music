<script setup>
defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Konfirmasi' },
    message: { type: String, default: '' },
    confirmText: { type: String, default: 'Hapus' },
    processing: { type: Boolean, default: false },
});

defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show">
            <div class="modal-backdrop fade show"></div>
            <div class="modal fade show d-block" tabindex="-1" role="dialog" @click.self="$emit('cancel')">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bs7-modal shadow">
                        <div class="modal-header border-0 pb-0">
                            <h2 class="modal-title h5 fw-bold">{{ title }}</h2>
                            <button type="button" class="btn-close" aria-label="Tutup" @click="$emit('cancel')"></button>
                        </div>
                        <div class="modal-body">{{ message }}</div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" :disabled="processing" @click="$emit('cancel')">
                                Batal
                            </button>
                            <button type="button" class="btn btn-danger" :disabled="processing" @click="$emit('confirm')">
                                <span v-if="processing" class="spinner-border spinner-border-sm me-1"></span>
                                {{ confirmText }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>