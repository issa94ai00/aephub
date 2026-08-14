<script setup>
import { computed } from 'vue';
import { clearFlash, state } from '../../../admin/state';

const flash = computed(() => state.flash);

const errorEntries = computed(() => {
    const err = state.errors;
    if (!err || typeof err !== 'object') {
        return [];
    }
    return Object.values(err)
        .flatMap((v) => (Array.isArray(v) ? v : [v]))
        .filter(Boolean);
});
</script>

<template>
    <div
        v-if="flash"
        class="admin-fade-up is-visible mb-4 flex items-start justify-between gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100"
    >
        <div>{{ flash }}</div>
        <button type="button" class="shrink-0 text-emerald-200/70 transition hover:text-emerald-100" aria-label="Close" @click="clearFlash">
            &times;
        </button>
    </div>

    <div
        v-if="errorEntries.length"
        class="admin-fade-up is-visible mb-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
    >
        <ul class="list-inside list-disc space-y-1">
            <li v-for="(msg, i) in errorEntries" :key="i">{{ msg }}</li>
        </ul>
    </div>
</template>
