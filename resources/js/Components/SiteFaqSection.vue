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

function padIndex(i) {
    return String(i + 1).padStart(2, '0');
}
</script>

<template>
    <section id="faq" class="site-faq mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-24 scroll-mt-28">
        <div class="reveal mb-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <div class="flex justify-start">
                        <p class="site-kicker">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 100-18 9 9 0 000 18z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 9.75h.008v.008H9.75V9.75zm.375 0a3.375 3.375 0 116.75 0c0 1.657-1.007 2.55-1.688 3.135-.34.292-.562.478-.562.99V15m0 3h.01" />
                            </svg>
                            {{ t.home?.faq_title }}
                        </p>
                    </div>
                    <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ t.home?.faq_sub }}</p>
                </div>
                <Link
                    v-if="showAllLink && routes.faq"
                    :href="routes.faq"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white/80 px-3.5 py-2 text-sm font-semibold text-emerald-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-emerald-200/70 hover:shadow-md dark:border-slate-600 dark:bg-slate-800/80 dark:text-emerald-400 dark:hover:border-emerald-800/50"
                >
                    {{ t.faq_page?.see_all }}
                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            <template v-if="faqs.length">
                <details
                    v-for="(faq, i) in faqs"
                    :key="i"
                    class="faq-card reveal group"
                    :open="i === 0"
                >
                    <summary class="faq-card__summary">
                        <span class="faq-card__index" aria-hidden="true">{{ padIndex(i) }}</span>
                        <span class="faq-card__question">{{ faq.question }}</span>
                        <span class="faq-card__chevron" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </span>
                    </summary>
                    <div class="faq-card__answer">
                        <div class="faq-card__answer-inner">
                            <div class="faq-card__answer-text border-t border-slate-100 dark:border-slate-800">
                                <p>{{ faq.answer }}</p>
                            </div>
                        </div>
                    </div>
                </details>
            </template>
            <p v-else class="text-sm text-slate-700 dark:text-slate-300 lg:col-span-2">{{ t.home?.faq_empty }}</p>
        </div>
    </section>
</template>
