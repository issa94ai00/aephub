<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SiteLayout from '../../Layouts/SiteLayout.vue';

const page = usePage();
const t = computed(() => page.props.translations.site);

defineProps({
    university: { type: Object, required: true },
    faculties: { type: Array, default: () => [] },
});
</script>

<template>
    <SiteLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
            <nav class="reveal text-sm font-medium" :aria-label="t.universities?.back_home">
                <a
                    :href="page.props.siteChrome?.nav_universities_href"
                    class="inline-flex items-center gap-2 rounded-xl text-emerald-700 transition hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300"
                >
                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    {{ t.universities?.back_home }}
                </a>
            </nav>

            <header class="reveal mt-8 max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400/90">
                    {{ t.universities?.page_kicker }}
                </p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-4xl">
                    {{ university.localized_name }}
                </h1>
                <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ t.universities?.page_lead }}
                </p>
            </header>

            <div v-if="!faculties.length" class="reveal site-panel mt-10 py-14 text-center">
                <p class="text-slate-600 dark:text-slate-400">{{ t.universities?.empty_faculties }}</p>
            </div>
            <div v-else class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="faculty in faculties"
                    :key="faculty.id"
                    class="reveal group relative overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-br from-white via-slate-50/90 to-emerald-50/30 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-white/70 transition duration-500 hover:-translate-y-1 hover:border-emerald-200/60 hover:shadow-xl dark:border-slate-700/60 dark:from-slate-900/95 dark:via-slate-900/80 dark:to-emerald-950/20 dark:ring-slate-700/40 dark:hover:border-emerald-800/45"
                >
                    <div
                        class="pointer-events-none absolute -end-8 -top-8 h-32 w-32 rounded-full bg-emerald-400/10 blur-2xl transition duration-500 group-hover:bg-emerald-400/20 dark:bg-emerald-500/10 dark:group-hover:bg-emerald-500/20"
                        aria-hidden="true"
                    />
                    <div
                        class="relative flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-200/50 bg-emerald-50 text-emerald-700 shadow-sm dark:border-emerald-800/40 dark:bg-emerald-950/50 dark:text-emerald-400"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                            />
                        </svg>
                    </div>
                    <h2 class="relative mt-5 text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">
                        {{ faculty.localized_name }}
                    </h2>
                    <p
                        v-if="faculty.study_years_count > 0"
                        class="relative mt-3 text-sm text-slate-600 dark:text-slate-400"
                    >
                        {{ t.universities?.years_count?.replace(':count', String(faculty.study_years_count)) }}
                    </p>
                </article>
            </div>
        </div>
    </SiteLayout>
</template>
