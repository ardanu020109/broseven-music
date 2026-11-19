<script setup>
defineProps({
    cover: { type: String, default: null },
    spinning: { type: Boolean, default: false },
    size: { type: String, default: 'md' }, // sm | md | lg
});
</script>

<template>
    <div class="vinyl" :class="[`is-${size}`, { 'is-spinning': spinning }]" aria-hidden="true">
        <div class="vinyl-label has-cover">
            <img v-if="cover" :src="cover" alt="" draggable="false" />
            <span v-else-if="size !== 'sm'">BS7!</span>
        </div>
    </div>
</template>