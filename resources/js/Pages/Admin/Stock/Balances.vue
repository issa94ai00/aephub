<script setup>
import { computed, reactive } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppLink from '../../../Admin/AppLink.vue';
import { router } from '../../../Admin/adminRouter';
import { useAdminHead } from '../../../Admin/useAdminHead';
import Toaster from '../Partials/Toaster.vue';
import Pagination from '../Partials/Pagination.vue';
import { fmt, localizedName, unitLabel } from '../Partials/helpers';

const props = defineProps({
    products: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
    search: { type: String, default: '' },
    lowStock: { type: Boolean, default: false },
    stats: { type: Object, default: () => ({}) },
    translations: { type: Object, default: () => ({}) },
    locale: { type: String, default: 'ar' },
});

const t = computed(() => props.translations?.admin ?? {});
const locale = computed(() => props.locale ?? 'ar');

const rows = computed(() => props.products?.data ?? []);

const filters = reactive({
    search: props.search ?? '',
    low: props.lowStock ? '1' : '',
});

const hasFilters = computed(() => filters.search !== '' || filters.low === '1');

useAdminHead(t.value.stock?.balances_title ?? '');

function apply() {
    const query = {};
    if (filters.search) {
        query.search = filters.search;
    }
    if (filters.low === '1') {
        query.low_stock = '1';
    }
    router.replace({ path: '/admin/stock/balances', query });
}

function clearFilters() {
    filters.search = '';
    filters.low = '';
    router.replace('/admin/stock/balances');
}

function exportUrl() {
    const q = new URLSearchParams();
    if (filters.search) {
        q.set('search', filters.search);
    }
    if (filters.low === '1') {
        q.set('low_stock', '1');
    }
    const s = q.toString();

    return `/admin/stock/balances/export${s ? `?${s}` : ''}`;
}

function qty(p, w) {
    return p.by_warehouse?.[String(w.id)] ?? 0;
}

function isLow(p) {
    return Number(p.total) <= Number(p.min_stock);
}
</script>

<template>
    <AdminLayout :heading="t.stock?.balances_heading ?? ''" :subheading="t.stock?.balances_sub ?? ''">
        <div class="admin-content">
            <Toaster />

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <AppLink href="/admin/stock" class="inline-flex items-center gap-1 text-xs text-emerald-200 hover:underline">
                    <span aria-hidden="true">&larr;</span>
                    {{ t.stock?.back_stock }}
                </AppLink>
                <AppLink
                    :href="exportUrl()"
                    class="admin-btn inline-flex items-center gap-1.5 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white/80 hover:bg-white/10"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"
                        />
                    </svg>
                    {{ t.stock?.export_csv }}
                </AppLink>
            </div>

            <div class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.stock?.total_products }}</div>
                    <div class="mt-1 text-2xl font-bold text-white">{{ fmt(stats.products) }}</div>
                </div>
                <div class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.stock?.total_warehouses }}</div>
                    <div class="mt-1 text-2xl font-bold text-white">{{ fmt(stats.warehouses) }}</div>
                </div>
                <div class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.stock?.total_units }}</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-100">{{ fmt(stats.units) }}</div>
                </div>
                <div class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.stock?.low_stock_count }}</div>
                    <div class="mt-1 text-2xl font-bold text-amber-200">{{ fmt(stats.low) }}</div>
                </div>
            </div>

            <form @submit.prevent="apply" class="mb-4 flex flex-wrap items-center gap-2">
                <input
                    v-model="filters.search"
                    type="text"
                    :placeholder="t.stock?.balances_search_placeholder"
                    class="min-w-56 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white placeholder-white/35 focus:border-emerald-400/40 focus:outline-none"
                />
                <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-white/70">
                    <input v-model="filters.low" type="checkbox" value="1" false-value="" class="h-3.5 w-3.5 rounded accent-amber-500" />
                    {{ t.stock?.balances_filter_low }}
                </label>
                <button
                    type="submit"
                    class="admin-btn rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white/80 hover:bg-white/10"
                >
                    {{ t.stock?.apply }}
                </button>
                <button v-if="hasFilters" type="button" @click="clearFilters" class="text-xs text-white/50 hover:text-emerald-200">
                    {{ t.stock?.clear }}
                </button>
            </form>

            <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                            <tr>
                                <th class="px-4 py-3 text-start">#</th>
                                <th class="px-4 py-3 text-start">{{ t.stock?.col_sku }}</th>
                                <th class="px-4 py-3 text-start">{{ t.stock?.col_name }}</th>
                                <th class="px-4 py-3 text-start">{{ t.stock?.col_unit }}</th>
                                <th v-for="w in warehouses" :key="w.id" class="px-4 py-3 text-center">
                                    <div>{{ localizedName(w, locale) }}</div>
                                    <div v-if="w.code" class="mt-0.5 font-mono text-[10px] text-white/35" dir="ltr">{{ w.code }}</div>
                                </th>
                                <th class="px-4 py-3 text-center">{{ t.stock?.col_total }}</th>
                                <th class="px-4 py-3 text-center">{{ t.stock?.col_status }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="p in rows" :key="p.id" class="hover:bg-white/[0.02]">
                                <td class="px-4 py-3 text-white/60">{{ p.id }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="p.sku"
                                        class="rounded-md border border-white/10 bg-white/5 px-2 py-0.5 font-mono text-xs text-emerald-100/90"
                                        dir="ltr"
                                    >
                                        {{ p.sku }}
                                    </span>
                                    <span v-else class="text-white/35">—</span>
                                </td>
                                <td class="px-4 py-3 text-white">
                                    <div class="font-medium">{{ localizedName(p, locale) }}</div>
                                    <div v-if="p.name_en && locale === 'ar'" class="mt-0.5 text-xs text-white/45" dir="ltr">
                                        {{ p.name_en }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-white/60">{{ unitLabel(p.unit, t.value) }}</td>
                                <td v-for="w in warehouses" :key="w.id" class="px-4 py-3 text-center">
                                    <span class="font-semibold text-white" :class="qty(p, w) <= 0 ? 'text-white/35' : ''" dir="ltr">
                                        {{ fmt(qty(p, w)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="font-bold text-emerald-100" dir="ltr">{{ fmt(p.total) }}</div>
                                    <div v-if="p.min_stock > 0" class="text-[10px] text-white/40">
                                        {{ t.stock?.col_reorder }} {{ fmt(p.min_stock) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="isLow(p)" class="admin-badge admin-badge--amber">{{ t.stock?.low }}</span>
                                    <span v-else class="admin-badge admin-badge--green">{{ t.stock?.ok }}</span>
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td :colspan="warehouses.length + 5" class="px-4 py-10 text-center text-white/55">
                                    {{ t.stock?.balances_empty }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="props.products?.links ?? []" />
            </div>
        </div>
    </AdminLayout>
</template>
