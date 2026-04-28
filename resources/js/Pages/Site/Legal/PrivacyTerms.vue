<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SiteLayout from '../../../Layouts/SiteLayout.vue';

defineProps({
    seo: { type: Object, required: true },
});

const page = usePage();
const L = computed(() => page.props.translations.legal);
const tSite = computed(() => page.props.translations.site);
const routes = computed(() => page.props.siteChrome?.routes ?? {});
</script>

<template>
    <SiteLayout>
        <Head :title="seo.title">
            <meta name="description" :content="seo.description" head-key="description" />
        </Head>

        <div class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <article class="reveal site-panel space-y-10 p-6 sm:p-8">
                <header class="border-b border-slate-200/80 pb-6 dark:border-slate-700/60">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">
                        {{ L.page_heading }}
                    </h1>
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">{{ L.updated_notice }}</p>
                </header>

                <section class="space-y-5 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-50">{{ L.privacy_heading }}</h2>
                    <p>{{ L.privacy_intro }}</p>

                    <div class="space-y-1">
                        <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ L.privacy_use_title }}</h3>
                        <p>{{ L.privacy_use_body }}</p>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ L.privacy_sharing_title }}</h3>
                        <p>{{ L.privacy_sharing_body }}</p>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ L.privacy_content_title }}</h3>
                        <p>{{ L.privacy_content_body }}</p>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ L.privacy_screen_title }}</h3>
                        <p>{{ L.privacy_screen_body }}</p>
                    </div>
                </section>

                <section class="space-y-5 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-50">{{ L.terms_heading }}</h2>

                    <div class="space-y-3">
                        <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ L.terms_s1_title }}</h3>
                        <ul class="list-disc space-y-2 ps-5">
                            <li v-for="(line, i) in L.terms_s1_items || []" :key="i">{{ line }}</li>
                        </ul>
                    </div>

                    <div class="space-y-3">
                        <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ L.terms_s2_title }}</h3>
                        <ul class="list-disc space-y-2 ps-5">
                            <li v-for="(line, i) in L.terms_s2_items || []" :key="i">{{ line }}</li>
                        </ul>
                    </div>

                    <div class="space-y-3">
                        <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ L.terms_s3_title }}</h3>
                        <p>{{ L.terms_s3_intro }}</p>
                        <ul class="list-disc space-y-2 ps-5">
                            <li v-for="(line, i) in L.terms_s3_violations || []" :key="i">{{ line }}</li>
                        </ul>
                        <p class="pt-1">{{ L.terms_s3_actions_intro }}</p>
                        <ul class="list-disc space-y-2 ps-5">
                            <li v-for="(line, i) in L.terms_s3_actions || []" :key="i">{{ line }}</li>
                        </ul>
                    </div>

                    <div class="space-y-3">
                        <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ L.terms_s4_title }}</h3>
                        <ul class="list-disc space-y-2 ps-5">
                            <li v-for="(line, i) in L.terms_s4_items || []" :key="i">{{ line }}</li>
                        </ul>
                    </div>
                </section>

                <p class="border-t border-slate-200/80 pt-6 text-center text-xs text-slate-500 dark:border-slate-700/60 dark:text-slate-500">
                    <Link
                        :href="routes.register"
                        class="font-medium text-emerald-700 underline hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
                        >{{ tSite.nav?.subscribe }}</Link
                    >
                </p>
            </article>
        </div>
    </SiteLayout>
</template>
