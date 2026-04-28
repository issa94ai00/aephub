<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const t = computed(() => page.props.translations.site);
const routes = computed(() => page.props.siteChrome?.routes ?? {});

defineProps({
    faqs: { type: Array, default: () => [] },
    /** When true, show link to full FAQ page (home only). */
    showAllLink: { type: Boolean, default: false },
});
</script>

<template>
    <section id="faq" class="site-faq mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-24">
        <div class="reveal mb-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
                        {{ t.home?.faq_title }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ t.home?.faq_sub }}</p>
                </div>
                <Link
                    v-if="showAllLink && routes.faq"
                    :href="routes.faq"
                    class="shrink-0 text-sm font-semibold text-emerald-700 transition hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300"
                >
                    {{ t.faq_page?.see_all }}
                </Link>
            </div>
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            <template v-if="faqs.length">
                <details
                    v-for="(faq, i) in faqs"
                    :key="i"
                    class="reveal group rounded-3xl border border-slate-200/70 bg-white/75 p-5 shadow-lg shadow-slate-900/5 ring-1 ring-white/70 backdrop-blur-xl transition duration-300 open:border-emerald-200/50 open:shadow-xl dark:border-slate-700/60 dark:bg-slate-900/70 dark:ring-slate-700/35 dark:open:border-emerald-800/40 sm:p-6"
                >
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-bold text-slate-900 dark:text-slate-100"
                    >
                        <span>{{ faq.question }}</span>
                        <span
                            class="site-faq-chevron grid h-8 w-8 shrink-0 place-items-center rounded-full border border-slate-200/80 bg-slate-50 text-slate-500 transition duration-300 group-open:border-emerald-200/60 group-open:bg-emerald-50 group-open:text-emerald-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400 dark:group-open:border-emerald-800/50 dark:group-open:bg-emerald-950/50 dark:group-open:text-emerald-300"
                            aria-hidden="true"
                            >⌄</span
                        >
                    </summary>
                    <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ faq.answer }}</p>
                </details>
            </template>
            <p v-else class="text-sm text-slate-600 dark:text-slate-400 lg:col-span-2">{{ t.home?.faq_empty }}</p>
        </div>
    </section>
</template>
