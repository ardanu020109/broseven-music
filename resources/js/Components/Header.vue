<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.role === 'superadmin');
const roleLabel = computed(() => (isAdmin.value ? 'Superadmin' : 'Listener'));

function logout() {
    router.post('/logout');
}
</script>

<template>
    <header class="bs7-topbar">
        <div class="container d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="bs7-logo-dot"></span>
                <strong>BroSeven! Music</strong>
            </div>

            <div v-if="user" class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <div class="small fw-semibold">{{ user.name }}</div>
                    <span class="bs7-role-badge" :class="isAdmin ? 'is-superadmin' : 'is-listener'">
                        {{ roleLabel }}
                    </span>
                </div>
                <button type="button" class="btn btn-outline-light btn-sm" @click="logout">Logout</button>
            </div>
        </div>
    </header>
</template>