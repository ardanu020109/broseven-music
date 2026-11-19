<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    listener: { type: Object, default: null },
});

const isEdit = computed(() => !!props.listener);
const showPassword = ref(false);

const form = useForm({
    name: props.listener?.name ?? '',
    email: props.listener?.email ?? '',
    password: '',
    is_active: props.listener?.is_active ?? true,
});

function generatePassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    const bytes = new Uint32Array(12);

    window.crypto.getRandomValues(bytes);

    form.password = Array.from(bytes, (b) => chars[b % chars.length]).join('');
    form.clearErrors('password');
    showPassword.value = true;
}

function submit() {
    if (isEdit.value) {
        form.put(`/admin/listeners/${props.listener.id}`, { preserveScroll: true });
    } else {
        form.post('/admin/listeners', { preserveScroll: true });
    }
}
</script>

<template>
    <form novalidate @submit.prevent="submit">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label fw-semibold">Nama <span class="text-danger">*</span></label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.name }"
                    maxlength="100"
                    autofocus
                />
                <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.email }"
                    maxlength="190"
                    autocomplete="off"
                />
                <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
            </div>

            <div class="col-12">
                <label for="password" class="form-label fw-semibold">
                    Password <span v-if="!isEdit" class="text-danger">*</span>
                    <span v-else class="text-secondary fw-normal">(kosongkan jika tidak diubah)</span>
                </label>
                <div class="input-group">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.password }"
                        maxlength="72"
                        autocomplete="new-password"
                        :placeholder="isEdit ? 'Password baru (opsional)' : 'Minimal 8 karakter'"
                    />
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                        @click="showPassword = !showPassword"
                    >
                        <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary" @click="generatePassword">
                        <i class="bi bi-magic me-1"></i> Buat otomatis
                    </button>
                </div>
                <div v-if="form.errors.password" class="invalid-feedback d-block">{{ form.errors.password }}</div>
                <div class="form-text">
                    Catat password sebelum menyimpan dan berikan kepada listener. Setelah disimpan, password tidak bisa dilihat lagi.
                    <template v-if="isEdit">Mengganti password akan mengeluarkan listener dari semua perangkat.</template>
                </div>
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input id="is_active" v-model="form.is_active" type="checkbox" role="switch" class="form-check-input" />
                    <label for="is_active" class="form-check-label fw-semibold">Akun aktif</label>
                </div>
                <div class="form-text">Listener yang nonaktif tidak dapat login dan langsung dikeluarkan dari semua perangkat.</div>
                <div v-if="form.errors.is_active" class="invalid-feedback d-block">{{ form.errors.is_active }}</div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end mt-4">
            <Link :href="isEdit ? `/admin/listeners/${listener.id}` : '/admin/listeners'" class="btn btn-outline-secondary">Batal</Link>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEdit ? 'Simpan Perubahan' : 'Tambah Listener' }}
            </button>
        </div>
    </form>
</template>