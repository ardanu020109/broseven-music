<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToastStore } from '../Stores/toast';

const page = usePage();
const toast = useToastStore();

const visible = ref(false);
const text = ref('');
const type = ref('success');
let timer;

function display(message, kind) {
    text.value = message;
    type.value = kind;
    visible.value = true;
    clearTimeout(timer);
    timer = setTimeout(() => (visible.value = false), 4500);
}

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.status) {
            display(flash.status, 'success');
        } else if (flash?.message) {
            display(flash.message, 'warning');
        }
    },
    { immediate: true, deep: true },
);

watch(
    () => toast.tick,
    () => display(toast.message, toast.type),
);

const classes = { success: 'alert-success', warning: 'alert-warning', error: 'alert-danger' };
</script>

<template>
    <Transition name="bs7-fade">
        <div v-if="visible" class="bs7-toast alert shadow" :class="classes[type] ?? 'alert-success'" role="alert">
            <span>{{ text }}</span>
            <button type="button" class="btn-close" aria-label="Tutup" @click="visible = false"></button>
        </div>
    </Transition>
</template>