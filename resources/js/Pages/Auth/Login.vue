<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const flashMessage = computed(() => page.props.flash?.message);
const flashStatus = computed(() => page.props.flash?.status);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Masuk" />

    <main class="bs7-hero">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="vinyl is-spinning">
                        <div class="vinyl-label"><span>BS7!</span></div>
                    </div>
                </div>

                <div class="col-lg-5 col-md-8 mx-auto">
                    <div class="text-center mb-4">
                        <h1 class="h2 fw-bold bs7-title mb-2">BroSeven! Music</h1>
                        <p class="mb-0" style="opacity: 0.8;">Masuk untuk membuka koleksi musik Anda.</p>
                    </div>

                    <div class="bs7-auth-card">
                        <div v-if="flashStatus" class="alert alert-info py-2">{{ flashStatus }}</div>
                        <div v-if="flashMessage" class="alert alert-warning py-2">{{ flashMessage }}</div>

                        <form @submit.prevent="submit" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.email }"
                                    placeholder="nama@email.com"
                                    autocomplete="username"
                                    autofocus
                                    required
                                />
                                <div v-if="form.errors.email" class="invalid-feedback d-block">
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.password }"
                                    placeholder="Password"
                                    autocomplete="current-password"
                                    required
                                />
                                <div v-if="form.errors.password" class="invalid-feedback d-block">
                                    {{ form.errors.password }}
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input id="remember" v-model="form.remember" type="checkbox" class="form-check-input" />
                                <label for="remember" class="form-check-label">Ingat saya</label>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                Masuk
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>