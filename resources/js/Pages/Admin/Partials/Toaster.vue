<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { clearFlash, setErrors, state } from '../../../Admin/state';

const visible = ref(false);
const marker = ref('');
let timer = null;

const flashText = computed(() => state.flash ?? '');

const errorEntries = computed(() => {
    const err = state.errors;
    if (!err || typeof err !== 'object') {
        return [];
    }
    return Object.values(err)
        .flatMap((v) => (Array.isArray(v) ? v : [v]))
        .filter(Boolean);
});

const errorText = computed(() => errorEntries.value.join('|'));

function dismiss() {
    visible.value = false;
    clearFlash();
    setErrors({});
    if (timer) {
        clearTimeout(timer);
        timer = null;
    }
}

watch(
    [flashText, errorText],
    ([f, e]) => {
        const current = `${f}::${e}`;

        if (f === '' && e === '') {
            visible.value = false;
            marker.value = current;
            return;
        }

        if (current === marker.value && visible.value) {
            return;
        }

        marker.value = current;
        visible.value = true;

        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(dismiss, 5000);
    }
);

onBeforeUnmount(() => {
    if (timer) {
        clearTimeout(timer);
    }
});
</script>

<template>
    <div class="pointer-events-none fixed inset-x-4 top-5 z-50 flex flex-col items-center gap-2 sm:inset-x-auto sm:end-6 sm:items-end">
        <transition-group name="toast">
            <div
                v-if="visible && flashText"
                :key="'flash'"
                class="toast pointer-events-auto flex w-full max-w-sm items-start justify-between gap-3 rounded-2xl border border-emerald-500/30 bg-[#0e1713]/95 px-4 py-3 text-sm text-emerald-100 shadow-2xl shadow-black/40 backdrop-blur"
                role="status"
            >
                <span class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span>{{ flashText }}</span>
                </span>
                <button type="button" class="shrink-0 text-emerald-200/70 transition hover:text-emerald-100" aria-label="Close" @click="dismiss">
                    &times;
                </button>
            </div>

            <div
                v-if="visible && errorEntries.length"
                :key="'errors'"
                class="toast pointer-events-auto flex w-full max-w-sm items-start justify-between gap-3 rounded-2xl border border-rose-500/30 bg-[#170f11]/95 px-4 py-3 text-sm text-rose-100 shadow-2xl shadow-black/40 backdrop-blur"
                role="alert"
            >
                <ul class="list-inside list-disc space-y-1">
                    <li v-for="(m, i) in errorEntries" :key="i">{{ m }}</li>
                </ul>
                <button type="button" class="shrink-0 text-rose-200/70 transition hover:text-rose-100" aria-label="Close" @click="dismiss">
                    &times;
                </button>
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-0.5rem);
}
</style>
