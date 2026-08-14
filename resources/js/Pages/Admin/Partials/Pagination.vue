<script setup>
import { RouterLink } from 'vue-router';
import { toPath } from '../../../admin/adminRouter';

defineProps({
    links: { type: Array, default: () => [] },
});
</script>

<template>
    <div v-if="links && links.length" class="border-t border-white/10 px-4 py-3">
        <nav class="flex flex-wrap items-center justify-center gap-1 text-xs" aria-label="Pagination">
            <template v-for="(l, i) in links" :key="i">
                <span
                    v-if="!l.url || l.active"
                    class="cursor-not-allowed rounded-lg px-2.5 py-1 text-white/30"
                    :class="{ 'bg-emerald-500/20 font-semibold text-emerald-100': l.active }"
                    v-html="l.label"
                />
                <RouterLink
                    v-else
                    :to="toPath(l.url)"
                    class="rounded-lg px-2.5 py-1 text-white/70 transition hover:bg-white/10 hover:text-white"
                    v-html="l.label"
                />
            </template>
        </nav>
    </div>
</template>
