<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: { type: Array, default: () => [] },
});

const items = computed(() =>
    props.links.map((link) => {
        let text = String(link.label).replace(/&laquo;|&raquo;/g, '').trim();

        if (/previous/i.test(text)) {
            text = '\u2039';
        } else if (/next/i.test(text)) {
            text = '\u203A';
        }

        return { ...link, text };
    }),
);
</script>

<template>
    <nav v-if="links.length > 3" aria-label="Navigasi halaman">
        <ul class="pagination bs7-pagination justify-content-center flex-wrap mb-0">
            <li
                v-for="(item, index) in items"
                :key="index"
                class="page-item"
                :class="{ active: item.active, disabled: !item.url }"
            >
                <Link v-if="item.url" class="page-link" :href="item.url" preserve-scroll>{{ item.text }}</Link>
                <span v-else class="page-link">{{ item.text }}</span>
            </li>
        </ul>
    </nav>
</template>