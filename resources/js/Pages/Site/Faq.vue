<script setup>
import SiteFaqSection from '../../Components/SiteFaqSection.vue';
import SiteLayout from '../../Layouts/SiteLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const t = computed(() => page.props.translations.site);
const routes = computed(() => page.props.siteChrome?.routes ?? {});

defineProps({
    faqs: { type: Array, default: () => [] },
});
</script>

<template>
    <SiteLayout>
        <Head :title="t.faq_page?.document_title ?? t.home?.faq_title">
            <meta
                v-if="t.faq_page?.meta_description"
                name="description"
                :content="t.faq_page.meta_description"
                head-key="description"
            />
        </Head>

        <div class="border-b border-slate-200/80 bg-white/60 py-3 dark:border-slate-800/80 dark:bg-slate-950/40">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-2 px-4 text-sm sm:px-6 lg:px-8">
                <Link
                    :href="routes.home ?? '/'"
                    class="font-medium text-emerald-700 transition hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300"
                >
                    {{ t.faq_page?.breadcrumb_home }}
                </Link>
                <span class="text-slate-400" aria-hidden="true">/</span>
                <span class="text-slate-600 dark:text-slate-400">{{ t.home?.faq_title }}</span>
            </div>
        </div>

        <div class="pt-6 sm:pt-10">
            <SiteFaqSection :faqs="faqs" />
        </div>
    </SiteLayout>
</template>
