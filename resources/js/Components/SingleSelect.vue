<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    id: { type: String, default: null },
    ariaLabel: { type: String, default: null },
    placeholder: { type: String, default: 'Pilih...' },
    emptyLabel: { type: String, default: null },
    searchPlaceholder: { type: String, default: 'Ketik untuk mencari...' },
    noResultsText: { type: String, default: 'Tidak ada hasil' },
    emptyText: { type: String, default: 'Belum ada pilihan' },
    labelKey: { type: String, default: 'name' },
    valueKey: { type: String, default: 'id' },
    clearable: { type: Boolean, default: true },
    disabled: { type: Boolean, default: false },
    invalid: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const uid = `bs7-select-${Math.random().toString(36).slice(2, 9)}`;
const root = ref(null);
const trigger = ref(null);
const searchInput = ref(null);
const listEl = ref(null);
const open = ref(false);
const query = ref('');
const activeIndex = ref(0);

const hasValue = computed(
    () => props.modelValue !== '' && props.modelValue !== null && props.modelValue !== undefined,
);

const selected = computed(() => {
    if (!hasValue.value) return null;

    return props.options.find((o) => String(o[props.valueKey]) === String(props.modelValue)) ?? null;
});

const selectedLabel = computed(() => (selected.value ? selected.value[props.labelKey] : null));
const showClear = computed(() => props.clearable && hasValue.value && !props.disabled);

function normalize(text) {
    return String(text ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();
}

const items = computed(() => {
    const q = normalize(query.value.trim());

    const list = props.options
        .filter((o) => !q || normalize(o[props.labelKey]).includes(q))
        .map((o) => ({
            key: `o-${o[props.valueKey]}`,
            value: o[props.valueKey],
            label: o[props.labelKey],
            isEmpty: false,
        }));

    if (props.emptyLabel && !q) {
        list.unshift({ key: 'empty', value: '', label: props.emptyLabel, isEmpty: true });
    }

    return list;
});

const filteredCount = computed(() => items.value.filter((i) => !i.isEmpty).length);

function isSelected(item) {
    return item.isEmpty ? !hasValue.value : String(item.value) === String(props.modelValue);
}

function scrollActiveIntoView() {
    const list = listEl.value;
    const el = list?.children[activeIndex.value];

    if (!list || !el) return;

    if (el.offsetTop < list.scrollTop) {
        list.scrollTop = el.offsetTop;
    } else if (el.offsetTop + el.offsetHeight > list.scrollTop + list.clientHeight) {
        list.scrollTop = el.offsetTop + el.offsetHeight - list.clientHeight;
    }
}

async function openMenu() {
    if (props.disabled || open.value) return;

    open.value = true;
    query.value = '';

    const index = items.value.findIndex(isSelected);
    activeIndex.value = index >= 0 ? index : 0;

    await nextTick();
    searchInput.value?.focus();
    scrollActiveIntoView();
}

function closeMenu(focusTrigger = false) {
    if (!open.value) return;

    open.value = false;
    query.value = '';

    if (focusTrigger) {
        trigger.value?.focus();
    }
}

function toggle() {
    if (open.value) {
        closeMenu(true);
    } else {
        openMenu();
    }
}

function choose(item) {
    emit('update:modelValue', item.value);
    closeMenu(true);
}

function clear() {
    emit('update:modelValue', '');
    trigger.value?.focus();
}

function move(step) {
    const total = items.value.length;

    if (!total) return;

    activeIndex.value = (activeIndex.value + step + total) % total;
    nextTick(scrollActiveIntoView);
}

function onSearchKeydown(event) {
    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            move(1);
            break;
        case 'ArrowUp':
            event.preventDefault();
            move(-1);
            break;
        case 'Enter': {
            event.preventDefault();
            const item = items.value[activeIndex.value];
            if (item) choose(item);
            break;
        }
        case 'Escape':
            event.preventDefault();
            event.stopPropagation();
            closeMenu(true);
            break;
        case 'Tab':
            closeMenu(false);
            break;
    }
}

watch(query, () => {
    activeIndex.value = 0;
});

function onDocumentMouseDown(event) {
    if (open.value && root.value && !root.value.contains(event.target)) {
        closeMenu(false);
    }
}

onMounted(() => document.addEventListener('mousedown', onDocumentMouseDown));
onBeforeUnmount(() => document.removeEventListener('mousedown', onDocumentMouseDown));
</script>

<template>
    <div
        ref="root"
        class="bs7-select"
        :class="{ 'is-open': open, 'is-invalid': invalid, 'is-disabled': disabled, 'has-clear': showClear }"
    >
        <button
            :id="id"
            ref="trigger"
            type="button"
            class="bs7-select-trigger"
            role="combobox"
            aria-haspopup="listbox"
            :aria-expanded="open"
            :aria-controls="`${uid}-list`"
            :aria-label="ariaLabel"
            :disabled="disabled"
            @click="toggle"
            @keydown.down.prevent="openMenu"
            @keydown.up.prevent="openMenu"
        >
            <span class="bs7-select-value text-truncate" :class="{ 'is-placeholder': !selectedLabel }">
                {{ selectedLabel ?? (placeholder || emptyLabel) }}
            </span>
            <i class="bi bi-chevron-down bs7-select-caret"></i>
        </button>

        <button
            v-if="showClear"
            type="button"
            class="bs7-select-clear"
            aria-label="Hapus pilihan"
            @click.stop="clear"
        >
            <i class="bi bi-x-lg"></i>
        </button>

        <div v-if="open" class="bs7-select-menu">
            <div class="bs7-select-search">
                <i class="bi bi-search"></i>
                <input
                    ref="searchInput"
                    v-model="query"
                    type="text"
                    role="searchbox"
                    autocomplete="off"
                    :placeholder="searchPlaceholder"
                    @keydown="onSearchKeydown"
                />
            </div>

            <ul :id="`${uid}-list`" ref="listEl" class="bs7-select-list" role="listbox">
                <li
                    v-for="(item, index) in items"
                    :key="item.key"
                    role="option"
                    class="bs7-select-option"
                    :aria-selected="isSelected(item)"
                    :class="{
                        'is-active': index === activeIndex,
                        'is-selected': isSelected(item),
                        'is-empty': item.isEmpty,
                    }"
                    @mousedown.prevent
                    @click="choose(item)"
                    @mousemove="activeIndex = index"
                >
                    <span class="text-truncate">{{ item.label }}</span>
                    <i v-if="isSelected(item) && !item.isEmpty" class="bi bi-check-lg"></i>
                </li>
            </ul>

            <div v-if="!filteredCount" class="bs7-select-empty">
                {{ options.length ? noResultsText : emptyText }}
            </div>
        </div>
    </div>
</template>