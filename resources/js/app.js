import './axios';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.min.css';
import '../css/app.css';
import * as bootstrap from 'bootstrap';

import { createApp, h, watch } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { createPinia } from 'pinia';

import MusicPlayer from './Components/MusicPlayer.vue';
import { usePlayerStore } from './Stores/player';

window.bootstrap = bootstrap;

const appName = import.meta.env.VITE_APP_NAME || 'BroSeven! Music';
const pages = import.meta.glob('./Pages/**/*.vue');
const pinia = createPinia();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const page = pages[`./Pages/${name}.vue`];
        if (!page) {
            throw new Error(`Halaman Inertia tidak ditemukan: ${name}`);
        }
        return page();
    },
    setup({ el, App, props, plugin }) {
        // Player berada di luar halaman Inertia sehingga tidak dibuat ulang saat navigasi
        createApp({ render: () => [h(App, props), h(MusicPlayer)] })
            .use(plugin)
            .use(pinia)
            .mount(el);

        const player = usePlayerStore(pinia);
        let role = props.initialPage?.props?.auth?.user?.role ?? null;

        // Logout, sesi habis, atau akun dinonaktifkan: hentikan musik
        router.on('navigate', (event) => {
            const user = event.detail.page.props.auth?.user ?? null;

            role = user?.role ?? null;

            if (!user) {
                player.close();
            }
        });

        // Catat lagu yang mulai diputar (untuk Recently Played); hanya untuk listener pemilik lagu
        let lastLogged = null;

        watch(
            () => [player.current?.id ?? null, player.playing],
            ([id, isPlaying]) => {
                if (!id) {
                    lastLogged = null;

                    return;
                }

                if (isPlaying && id !== lastLogged && role === 'listener') {
                    lastLogged = id;
                    window.axios.post(`/songs/${id}/played`).catch(() => {});
                }
            },
        );

        // Hanya untuk pengujian di mode dev
        if (import.meta.env.DEV) {
            window.__bs7player = player;
        }
    },
    progress: { color: '#22D3EE' },
});