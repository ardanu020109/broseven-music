<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const page = usePage();
const user = computed(() => page.props.auth?.user);

const items = [
    { label: 'Dashboard', icon: 'bi-speedometer2', href: '/admin/dashboard' },
    { label: 'Manage Listeners', icon: 'bi-people', href: '/admin/listeners' },
    { label: 'Uploaded Music', icon: 'bi-cloud-arrow-up', href: '/admin/songs' },
    { label: 'Profile', icon: 'bi-person-circle', href: '/profile' },
];

function isActive(href) {
    const url = page.url.split('?')[0];

    return url === href || url.startsWith(href + '/');
}

function logout() {
    router.post('/logout');
}
</script>

<template>
    <aside class="bs7-sidebar" :class="{ 'is-open': open }">
        <div class="bs7-sidebar-brand">
            <span class="bs7-logo-dot"></span>
            <strong>BroSeven! Music</strong>
        </div>

        <nav class="flex-grow-1">
            <ul class="bs7-nav">
                <li v-for="item in items" :key="item.href">
                    <Link
                        :href="item.href"
                        class="bs7-nav-item"
                        :class="{ 'is-active': isActive(item.href) }"
                        @click="emit('close')"
                    >
                        <i class="bi" :class="item.icon"></i>
                        {{ item.label }}
                    </Link>
                </li>
            </ul>
        </nav>

        <div v-if="user" class="bs7-sidebar-user">
            <div class="fw-semibold text-truncate">{{ user.name }}</div>
            <div class="small text-truncate mb-2" style="opacity: 0.6;">{{ user.email }}</div>
            <span class="bs7-role-badge is-superadmin mb-3">Superadmin</span>
            <button type="button" class="btn btn-outline-light btn-sm w-100 mt-2" @click="logout">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </button>
        </div>
    </aside>
</template>