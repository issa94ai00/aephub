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
            <nav class="reveal text-sm font-medium" :aria-label="t.nav?.main">
                <a
                    :href="page.props.siteChrome?.nav_universities_href ?? '/'"
                    class="breadcrumb-link"
                >
                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    {{ t.universities?.back_home }}
                </a>
            </nav>

            <header class="reveal relative mt-8 overflow-hidden rounded-3xl border border-sky-200/50 bg-gradient-to-br from-sky-50/70 via-white to-white p-8 shadow-xl shadow-slate-900/5 ring-1 ring-white/70 dark:border-sky-800/40 dark:from-sky-950/30 dark:via-slate-900/80 dark:to-slate-900/80 dark:ring-slate-700/40 sm:p-10">
                <div
                    class="pointer-events-none absolute -end-16 -top-16 h-52 w-52 rounded-full bg-sky-400/10 blur-3xl dark:bg-sky-500/10"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute -bottom-16 -start-16 h-52 w-52 rounded-full bg-emerald-400/10 blur-3xl dark:bg-emerald-500/10"
                    aria-hidden="true"
                />
                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="max-w-3xl">
                        <p class="site-kicker site-kicker--sky">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            {{ t.universities?.page_kicker }}
                        </p>
                        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 dark:text-slate-50 sm:text-4xl">
                            {{ university.localized_name }}
                        </h1>
                        <p class="mt-3 max-w-2xl text-base leading-relaxed text-slate-700 dark:text-slate-300">
                            {{ t.universities?.page_lead }}
                        </p>
                    </div>
                    <div
                        class="grid grid-cols-3 gap-3 sm:grid-cols-1"
                    >
                        <div class="stat-card !p-3 text-center sm:!p-4">
                            <div class="stat-card__value text-gradient-brand">{{ faculties.length }}</div>
                            <div class="stat-card__label">
                                {{ t.universities?.faculty_count?.replace(':count', String(faculties.length)) }}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div v-if="!faculties.length" class="reveal site-panel mt-10 py-14 text-center">
                <p class="text-slate-700 dark:text-slate-300">{{ t.universities?.empty_faculties }}</p>
            </div>
            <div v-else class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
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
                        class="icon-badge border-emerald-200/50 bg-emerald-50 text-emerald-700 dark:border-emerald-800/40 dark:bg-emerald-950/50 dark:text-emerald-400"
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
                    <h2 class="relative mt-5 text-lg font-bold tracking-tight text-slate-950 dark:text-slate-50">
                        {{ faculty.localized_name }}
                    </h2>
                    <div
                        v-if="faculty.study_years_count > 0"
                        class="relative mt-3 flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"
                    >
                        <svg class="h-4 w-4 text-slate-600 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ t.universities?.years_count?.replace(':count', String(faculty.study_years_count)) }}
                    </div>
                </article>
            </div>
        </div>
    </SiteLayout>
</template>
