<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Pagination.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import SingleSelect from '../../../Components/SingleSelect.vue';
import { debounce } from '../../../Composables/useDebounce';
import { formatBytes, formatDateTime } from '../../../Composables/useFormat';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    listeners: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const statusOptions = [
    { id: 'active', name: 'Aktif' },
    { id: 'inactive', name: 'Nonaktif' },
];

function load() {
    router.get(
        '/admin/listeners',
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const debouncedLoad = debounce(load, 350);

watch(search, () => debouncedLoad());
watch(status, () => load());

const busy = ref(false);
const toDeactivate = ref(null);
const toDelete = ref(null);

function activate(listener) {
    router.patch(`/admin/listeners/${listener.id}/status`, { is_active: true }, { preserveScroll: true });
}

function deactivate() {
    if (!toDeactivate.value) return;

    busy.value = true;
    router.patch(
        `/admin/listeners/${toDeactivate.value.id}/status`,
        { is_active: false },
        {
            preserveScroll: true,
            onFinish: () => {
                busy.value = false;
                toDeactivate.value = null;
            },
        },
    );
}

function destroy() {
    if (!toDelete.value) return;

    busy.value = true;
    router.delete(`/admin/listeners/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
            toDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Manage Listeners" />

    <div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-0">Manage Listeners</h1>
                <div class="text-secondary small">{{ listeners.total }} listener</div>
            </div>
            <Link href="/admin/listeners/create" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i> Tambah Listener
            </Link>
        </div>

        <div class="row g-2 mb-4" style="max-width: 720px;">
            <div class="col-sm-7">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input v-model="search" type="search" class="form-control" placeholder="Cari nama atau email..." />
                </div>
            </div>
            <div class="col-sm-5">
                <SingleSelect
                    v-model="status"
                    :options="statusOptions"
                    placeholder="Semua status"
                    empty-label="Semua status"
                    aria-label="Filter status"
                    search-placeholder="Cari status..."
                />
            </div>
        </div>

        <div v-if="listeners.data.length" class="bs7-table-wrap mb-4">
            <div class="table-responsive">
                <table class="table bs7-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Listener</th>
                            <th>Status</th>
                            <th class="text-end">Lagu</th>
                            <th class="text-end d-none d-md-table-cell">Penyimpanan</th>
                            <th class="d-none d-lg-table-cell">Login terakhir</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in listeners.data" :key="item.id">
                            <td>
                                <Link :href="`/admin/listeners/${item.id}`" class="bs7-user-cell">
                                    <span class="bs7-avatar">{{ item.name.charAt(0).toUpperCase() }}</span>
                                    <span class="min-w-0">
                                        <span class="d-block fw-semibold text-truncate">{{ item.name }}</span>
                                        <span class="d-block small text-secondary text-truncate">{{ item.email }}</span>
                                    </span>
                                </Link>
                            </td>
                            <td>
                                <span class="bs7-status-badge" :class="item.is_active ? 'is-active' : 'is-inactive'">
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end">{{ item.songs_count }}</td>
                            <td class="text-end d-none d-md-table-cell">{{ formatBytes(item.songs_size) }}</td>
                            <td class="d-none d-lg-table-cell small text-secondary">{{ formatDateTime(item.last_login_at) }}</td>
                            <td class="text-end">
                                <div class="bs7-row-actions">
                                    <Link :href="`/admin/listeners/${item.id}`" class="bs7-icon-btn" title="Detail" aria-label="Detail">
                                        <i class="bi bi-eye"></i>
                                    </Link>
                                    <Link :href="`/admin/listeners/${item.id}/edit`" class="bs7-icon-btn" title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </Link>
                                    <button
                                        v-if="item.is_active"
                                        type="button"
                                        class="bs7-icon-btn"
                                        title="Nonaktifkan"
                                        aria-label="Nonaktifkan"
                                        @click="toDeactivate = item"
                                    >
                                        <i class="bi bi-slash-circle"></i>
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        class="bs7-icon-btn"
                                        title="Aktifkan"
                                        aria-label="Aktifkan"
                                        @click="activate(item)"
                                    >
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="bs7-icon-btn is-danger"
                                        title="Hapus"
                                        aria-label="Hapus"
                                        @click="toDelete = item"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else class="bs7-page-card bs7-empty mb-4">
            <i class="bi bi-people d-block mb-3"></i>
            <template v-if="filters.search || filters.status">
                <h2 class="h5">Tidak ada hasil</h2>
                <p class="mb-0">Tidak ada listener yang cocok dengan pencarian atau filter Anda.</p>
            </template>
            <template v-else>
                <h2 class="h5">Belum ada listener</h2>
                <p>Tambahkan listener pertama agar mereka bisa membangun koleksi musiknya.</p>
                <Link href="/admin/listeners/create" class="btn btn-primary">Tambah Listener</Link>
            </template>
        </div>

        <Pagination :links="listeners.links" />
    </div>

    <ConfirmModal
        :show="!!toDeactivate"
        title="Nonaktifkan listener?"
        :message="toDeactivate ? `${toDeactivate.name} tidak akan bisa login dan langsung dikeluarkan dari semua perangkat. Data koleksinya tetap tersimpan dan bisa diaktifkan kembali kapan saja.` : ''"
        confirm-text="Nonaktifkan"
        :processing="busy"
        @confirm="deactivate"
        @cancel="toDeactivate = null"
    />

    <ConfirmModal
        :show="!!toDelete"
        title="Hapus listener?"
        :message="toDelete ? `${toDelete.name} akan dihapus PERMANEN beserta ${toDelete.songs_count} lagu, ${toDelete.albums_count} album, ${toDelete.artists_count} artis, ${toDelete.playlists_count} playlist, dan seluruh file miliknya (${formatBytes(toDelete.songs_size)}). Tindakan ini tidak dapat dibatalkan.` : ''"
        :processing="busy"
        @confirm="destroy"
        @cancel="toDelete = null"
    />
</template>