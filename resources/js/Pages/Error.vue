<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    status: { type: Number, default: 500 },
});

const messages = {
    403: { title: 'Akses Ditolak', text: 'Anda tidak memiliki izin untuk membuka halaman ini.' },
    404: { title: 'Halaman Tidak Ditemukan', text: 'Halaman yang Anda cari tidak ada atau sudah dipindahkan.' },
    419: { title: 'Sesi Kedaluwarsa', text: 'Silakan muat ulang halaman lalu coba lagi.' },
    500: { title: 'Terjadi Kesalahan', text: 'Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.' },
    503: { title: 'Layanan Tidak Tersedia', text: 'Aplikasi sedang dalam pemeliharaan. Silakan coba lagi nanti.' },
};

const info = computed(() => messages[props.status] ?? messages[500]);
</script>

<template>
    <Head :title="`${status} - ${info.title}`" />

    <main class="bs7-hero">
        <div class="container py-5 text-center">
            <div class="display-1 fw-bold bs7-title">{{ status }}</div>
            <h1 class="h3 mb-2">{{ info.title }}</h1>
            <p class="mb-4" style="opacity: 0.8;">{{ info.text }}</p>
            <a href="/" class="btn btn-primary btn-lg">Kembali</a>
        </div>
    </main>
</template>