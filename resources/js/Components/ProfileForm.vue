<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { formatDateTime } from '../Composables/useFormat';

const props = defineProps({
    profile: { type: Object, required: true },
    emailEditable: { type: Boolean, default: true },
});

const isAdmin = computed(() => props.profile.role === 'superadmin');
const showPassword = ref(false);

const info = useForm({
    name: props.profile.name,
    email: props.profile.email,
});

const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function saveProfile() {
    info.put('/profile', { preserveScroll: true });
}

function savePassword() {
    password.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => password.reset(),
        onError: () => password.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="bs7-page-card h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="bs7-avatar is-lg">{{ profile.name.charAt(0).toUpperCase() }}</span>
                    <div class="min-w-0">
                        <div class="fw-bold text-truncate">{{ profile.name }}</div>
                        <div class="small text-secondary text-truncate">{{ profile.email }}</div>
                        <span class="bs7-role-badge mt-1" :class="isAdmin ? 'is-superadmin' : 'is-listener'">
                            {{ isAdmin ? 'Superadmin' : 'Listener' }}
                        </span>
                    </div>
                </div>
                <dl class="bs7-dl mb-0">
                    <dt>Anggota sejak</dt>
                    <dd>{{ formatDateTime(profile.created_at) }}</dd>
                    <dt>Login terakhir</dt>
                    <dd>{{ formatDateTime(profile.last_login_at) }}</dd>
                </dl>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="bs7-page-card mb-4">
                <h2 class="h5 fw-bold mb-3">Data profil</h2>
                <form novalidate @submit.prevent="saveProfile">
                    <div class="mb-3">
                        <label for="profile-name" class="form-label fw-semibold">Nama <span class="text-danger">*</span></label>
                        <input
                            id="profile-name"
                            v-model="info.name"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': info.errors.name }"
                            maxlength="100"
                        />
                        <div v-if="info.errors.name" class="invalid-feedback">{{ info.errors.name }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="profile-email" class="form-label fw-semibold">Email</label>
                        <input
                            id="profile-email"
                            v-model="info.email"
                            type="email"
                            class="form-control"
                            :class="{ 'is-invalid': info.errors.email }"
                            maxlength="190"
                            :disabled="!emailEditable"
                        />
                        <div v-if="info.errors.email" class="invalid-feedback">{{ info.errors.email }}</div>
                        <div v-if="!emailEditable" class="form-text">
                            Email superadmin diatur lewat <code>ADMIN_EMAIL</code> di file .env dan tidak dapat diubah di sini.
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary" :disabled="info.processing || !info.isDirty">
                            <span v-if="info.processing" class="spinner-border spinner-border-sm me-2"></span>
                            Simpan Profil
                        </button>
                    </div>
                </form>
            </div>

            <div class="bs7-page-card">
                <h2 class="h5 fw-bold mb-3">Ganti password</h2>
                <form novalidate @submit.prevent="savePassword">
                    <div class="mb-3">
                        <label for="current-password" class="form-label fw-semibold">Password saat ini</label>
                        <input
                            id="current-password"
                            v-model="password.current_password"
                            :type="showPassword ? 'text' : 'password'"
                            class="form-control"
                            :class="{ 'is-invalid': password.errors.current_password }"
                            autocomplete="current-password"
                        />
                        <div v-if="password.errors.current_password" class="invalid-feedback">{{ password.errors.current_password }}</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="new-password" class="form-label fw-semibold">Password baru</label>
                            <input
                                id="new-password"
                                v-model="password.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="form-control"
                                :class="{ 'is-invalid': password.errors.password }"
                                maxlength="72"
                                autocomplete="new-password"
                            />
                            <div v-if="password.errors.password" class="invalid-feedback">{{ password.errors.password }}</div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm-password" class="form-label fw-semibold">Ulangi password baru</label>
                            <input
                                id="confirm-password"
                                v-model="password.password_confirmation"
                                :type="showPassword ? 'text' : 'password'"
                                class="form-control"
                                maxlength="72"
                                autocomplete="new-password"
                            />
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                        <div class="form-check">
                            <input id="show-password" v-model="showPassword" type="checkbox" class="form-check-input" />
                            <label for="show-password" class="form-check-label small">Tampilkan password</label>
                        </div>
                        <button type="submit" class="btn btn-primary" :disabled="password.processing">
                            <span v-if="password.processing" class="spinner-border spinner-border-sm me-2"></span>
                            Ganti Password
                        </button>
                    </div>
                    <div class="form-text">Minimal 8 karakter dan harus berbeda dari password saat ini.</div>
                </form>
            </div>
        </div>
    </div>
</template>