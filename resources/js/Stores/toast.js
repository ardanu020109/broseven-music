import { ref } from 'vue';
import { defineStore } from 'pinia';

/**
 * Notifikasi singkat di sisi klien (untuk kejadian yang bukan hasil redirect server).
 * type: success | warning | error
 */
export const useToastStore = defineStore('toast', () => {
    const message = ref('');
    const type = ref('success');
    const tick = ref(0);

    function show(text, kind = 'success') {
        message.value = text;
        type.value = kind;
        tick.value += 1;
    }

    return { message, type, tick, show };
});