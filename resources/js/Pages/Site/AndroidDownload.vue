<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SiteLayout from '../../Layouts/SiteLayout.vue';

const page = usePage();
const t = computed(() => page.props.translations.site);
const chrome = computed(() => page.props.siteChrome);

defineProps({
    androidApkUrl: { type: String, default: '' },
});

const steps = computed(() => {
    const tr = t.value?.android ?? {};
    return [
        { n: '1', title: tr.step1_title, text: tr.step1_text },
        { n: '2', title: tr.step2_title, text: tr.step2_text },
        { n: '3', title: tr.step3_title, text: tr.step3_text },
    ];
});
</script>

<template>
    <SiteLayout>
        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-14">
            <section class="reveal site-panel">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400/90">
                        {{ t.android?.kicker }}
                    </p>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">
                        {{ t.android?.title }}
                    </h1>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ t.android?.lead }}</p>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-3">
                    <div
                        v-for="step in steps"
                        :key="step.n"
                        class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-5 shadow-inner transition duration-300 hover:border-emerald-200/50 dark:border-slate-700/60 dark:bg-slate-800/80"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-b from-amber-500 to-orange-600 text-sm font-bold text-white shadow-md shadow-orange-900/20"
                        >
                            {{ step.n }}
                        </div>
                        <h2 class="mt-4 text-sm font-bold text-slate-900 dark:text-slate-50">{{ step.title }}</h2>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ step.text }}</p>
                    </div>
                </div>

                <div class="mt-10 flex flex-wrap items-center gap-3">
                    <a
                        v-if="androidApkUrl"
                        :href="androidApkUrl"
                        class="site-btn-primary px-6 py-3 text-sm"
                        >{{ t.android?.download_apk }}</a
                    >
                    <span
                        v-else
                        class="inline-flex items-center rounded-2xl border border-amber-200/60 bg-amber-50/80 px-4 py-3 text-sm text-amber-900 shadow-sm dark:border-amber-800/40 dark:bg-amber-950/30 dark:text-amber-200"
                        >{{ t.android?.apk_missing }}</span
                    >
                    <a :href="chrome.nav_courses_href" class="site-btn-secondary px-6 py-3 text-sm">{{
                        t.android?.browse_courses
                    }}</a>
                </div>
            </section>
        </div>
    </SiteLayout>
</template>
