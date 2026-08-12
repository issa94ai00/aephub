<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [Number, String], default: '' },
    products: { type: Array, default: () => [] },
    placeholder: { type: String, default: '' },
    locale: { type: String, default: 'ar' },
    disabled: { type: Boolean, default: false },
    inputClass: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const root = ref(null);
const query = ref('');
const open = ref(false);
const highlight = ref(0);

function label(p) {
    return props.locale === 'en' && p.name_en ? p.name_en : p.name;
}

const selected = computed(() => props.products.find((p) => String(p.id) === String(props.modelValue)) ?? null);

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    const list = q === ''
        ? props.products
        : props.products.filter((p) => {
            const haystack = [p.name, p.name_en, p.sku, p.barcode, String(p.id)]
                .filter(Boolean)
                .map((v) => String(v).toLowerCase())
                .join(' ');

            return haystack.includes(q);
        });

    return list.slice(0, 8);
});

watch(
    () => props.modelValue,
    (val) => {
        const p = props.products.find((x) => String(x.id) === String(val));
        query.value = p ? label(p) : '';
    }
);

function select(p) {
    emit('update:modelValue', p.id);
    query.value = label(p);
    open.value = false;
}

function clear() {
    emit('update:modelValue', '');
    query.value = '';
    open.value = false;
}

function onInput() {
    open.value = true;
    highlight.value = 0;
}

function onFocus() {
    if (!props.disabled) {
        open.value = true;
        highlight.value = 0;
    }
}

function onKeydown(e) {
    if (!open.value) {
        if (e.key === 'ArrowDown' || e.key === 'Enter') {
            open.value = true;
        }
        return;
    }

    if (e.key === 'Escape') {
        open.value = false;
        return;
    }

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlight.value = (highlight.value + 1) % Math.max(filtered.value.length, 1);
        return;
    }

    if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlight.value = (highlight.value - 1 + Math.max(filtered.value.length, 1)) % Math.max(filtered.value.length, 1);
        return;
    }

    if (e.key === 'Enter') {
        const p = filtered.value[highlight.value];
        if (p) {
            e.preventDefault();
            select(p);
        }
        return;
    }

    if (e.key === 'Tab') {
        open.value = false;
    }
}

function onClickOutside(e) {
    if (root.value && !root.value.contains(e.target)) {
        open.value = false;
    }
}

onMounted(() => {
    document.addEventListener('mousedown', onClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onClickOutside);
});
</script>

<template>
    <div ref="root" class="relative">
        <input
            v-if="!selected"
            v-model="query"
            type="text"
            role="combobox"
            :aria-expanded="open"
            aria-autocomplete="list"
            :placeholder="placeholder"
            :disabled="disabled"
            :class="inputClass"
            @input="onInput"
            @focus="onFocus"
            @keydown="onKeydown"
        />

        <div v-else class="flex items-center gap-2" :class="inputClass">
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm text-white">{{ label(selected) }}</div>
                <div v-if="selected.sku || selected.barcode" class="truncate font-mono text-[10px] text-white/40" dir="ltr">
                    {{ selected.sku || selected.barcode }}
                </div>
            </div>
            <button
                type="button"
                class="shrink-0 rounded-lg px-1.5 py-0.5 text-white/40 transition hover:bg-white/10 hover:text-white"
                :aria-label="placeholder"
                @click="clear"
            >
                &times;
            </button>
        </div>

        <transition name="dropdown">
            <div
                v-if="open && !selected"
                class="absolute z-30 mt-1.5 w-full overflow-hidden rounded-xl border border-white/10 bg-[#0d1411]/95 shadow-2xl shadow-black/40 backdrop-blur"
            >
                <ul class="max-h-64 overflow-y-auto py-1" role="listbox">
                    <li
                        v-for="(p, i) in filtered"
                        :key="p.id"
                        role="option"
                        :aria-selected="i === highlight"
                        class="cursor-pointer px-3 py-2"
                        :class="i === highlight ? 'bg-emerald-500/15 text-emerald-100' : 'text-white/80 hover:bg-white/5'"
                        @mousedown.prevent="select(p)"
                        @mouseenter="highlight = i"
                    >
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <span class="min-w-0 truncate">{{ label(p) }}</span>
                            <span v-if="p.sku || p.barcode" class="shrink-0 font-mono text-[10px] text-white/40" dir="ltr">
                                {{ p.sku || p.barcode }}
                            </span>
                        </div>
                    </li>
                    <li v-if="filtered.length === 0" class="px-3 py-2 text-sm text-white/40">—</li>
                </ul>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-0.25rem);
}
</style>
