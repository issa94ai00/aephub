<script setup>
import { computed, reactive } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppLink from '../../../Admin/AppLink.vue';
import { router } from '../../../Admin/adminRouter';
import { useAdminForm } from '../../../Admin/useAdminForm';
import { useAdminHead } from '../../../Admin/useAdminHead';
import Alert from '../Partials/Alert.vue';
import Pagination from '../Partials/Pagination.vue';
import { fmt, fmtMoney, localizedName, unitLabel } from '../Partials/helpers';

const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    search: { type: String, default: '' },
    category: { type: String, default: null },
    low: { type: String, default: null },
    stats: { type: Object, default: () => ({}) },
    translations: { type: Object, default: () => ({}) },
    locale: { type: String, default: 'ar' },
});

const t = computed(() => props.translations?.admin ?? {});
const locale = computed(() => props.locale ?? 'ar');

const rows = computed(() => props.products?.data ?? []);

const filters = reactive({
    search: props.search,
    category: props.category ?? '',
    low: props.low ?? '',
});

const hasFilters = computed(() => props.search !== '' || (props.category ?? '') !== '' || props.low === '1');

const del = useAdminForm({});

useAdminHead(t.value.products?.title ?? '');

function apply() {
    const query = {};
    if (filters.search) {
        query.search = filters.search;
    }
    if (filters.category) {
        query.category = filters.category;
    }
    if (filters.low) {
        query.low = '1';
    }
    router.replace({ path: '/admin/products', query });
}

function clearFilters() {
    filters.search = '';
    filters.category = '';
    filters.low = '';
    router.replace('/admin/products');
}

function stockState(p) {
    const total = Number(p.total_units) || 0;
    return { total, low: total <= Number(p.min_stock) || 0 };
}

function destroy(id) {
    if (!window.confirm(t.value.products?.confirm_delete)) {
        return;
    }
    del.delete(`/admin/products/${id}`);
}
</script>

<template>
    <AdminLayout :heading="t.products?.heading ?? ''" :subheading="t.products?.subheading ?? ''">
        <div class="admin-content">
            <Alert />

            <div class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <AppLink href="/admin/products" class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.products?.stat_total }}</div>
                    <div class="mt-1 text-2xl font-bold text-white">{{ fmt(stats.products) }}</div>
                </AppLink>
                <AppLink href="/admin/products" class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.products?.stat_units }}</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-100">{{ fmt(stats.units) }}</div>
                </AppLink>
                <AppLink href="/admin/products?low=1" class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.products?.stat_low }}</div>
                    <div class="mt-1 text-2xl font-bold text-amber-200">{{ fmt(stats.low) }}</div>
                </AppLink>
                <AppLink href="/admin/warehouses" class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.products?.stat_warehouses }}</div>
                    <div class="mt-1 text-2xl font-bold text-white">{{ fmt(stats.warehouses) }}</div>
                </AppLink>
            </div>

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <form @submit.prevent="apply" class="flex flex-wrap items-center gap-2">
                    <input
                        v-model="filters.search"
                        type="text"
                        :placeholder="t.products?.search_placeholder"
                        class="rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white placeholder-white/35 focus:border-emerald-400/40 focus:outline-none"
                    />
                    <select
                        v-model="filters.category"
                        class="rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white focus:border-emerald-400/40 focus:outline-none"
                    >
                        <option value="">{{ t.products?.filter_category }}</option>
                        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                    </select>
                    <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-white/70">
                        <input v-model="filters.low" type="checkbox" value="1" false-value="" class="h-3.5 w-3.5 rounded accent-amber-500" />
                        {{ t.products?.filter_low }}
                    </label>
                    <button
                        type="submit"
                        class="admin-btn rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white/80 hover:bg-white/10"
                    >
                        {{ t.products?.apply }}
                    </button>
                    <button v-if="hasFilters" type="button" @click="clearFilters" class="text-xs text-white/50 hover:text-emerald-200">
                        {{ t.products?.clear }}
                    </button>
                </form>
                <AppLink
                    href="/admin/products/create"
                    class="admin-btn inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-900/25 hover:bg-emerald-500"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t.products?.new }}
                </AppLink>
            </div>

            <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                            <tr>
                                <th class="px-4 py-3 text-start">#</th>
                                <th class="px-4 py-3 text-start">{{ t.products?.col_sku }}</th>
                                <th class="px-4 py-3 text-start">{{ t.products?.col_name }}</th>
                                <th class="px-4 py-3 text-start">{{ t.products?.col_category }}</th>
                                <th class="px-4 py-3 text-center">{{ t.products?.col_unit }}</th>
                                <th class="px-4 py-3 text-center">{{ t.products?.col_stock }}</th>
                                <th class="px-4 py-3 text-start">{{ t.products?.col_warehouses }}</th>
                                <th class="px-4 py-3 text-start">{{ t.products?.col_price }}</th>
                                <th class="px-4 py-3 text-center">{{ t.products?.col_status }}</th>
                                <th class="px-4 py-3 text-end">{{ t.products?.col_actions }}</th>
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
                                <td class="px-4 py-3 text-white/60">{{ p.category || '—' }}</td>
                                <td class="px-4 py-3 text-center text-white/60">{{ unitLabel(p.unit, t.value) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="font-semibold text-white">{{ fmt(stockState(p).total) }}</div>
                                    <span v-if="stockState(p).low" class="admin-badge admin-badge--amber mt-1">
                                        {{ t.products?.low_stock }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="p.warehouses && p.warehouses.length" class="flex flex-wrap gap-1">
                                        <span
                                            v-for="w in p.warehouses"
                                            :key="w.id"
                                            class="admin-badge admin-badge--sky"
                                            :title="w.code || localizedName(w, locale)"
                                        >
                                            {{ w.code || localizedName(w, locale) }} · {{ fmt(w.quantity) }}
                                        </span>
                                    </div>
                                    <span v-else class="text-xs text-white/40">{{ t.products?.no_links }}</span>
                                </td>
                                <td class="px-4 py-3 text-white/70">
                                    <div class="text-xs text-white/50">{{ t.products?.cost }}: {{ fmtMoney(p.cost_price) }}</div>
                                    <div class="font-medium text-emerald-100">{{ t.products?.sale }}: {{ fmtMoney(p.sale_price) }}</div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="p.is_active" class="admin-badge admin-badge--green">{{ t.products?.active }}</span>
                                    <span v-else class="admin-badge admin-badge--neutral">{{ t.products?.inactive }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-end">
                                    <AppLink
                                        :href="`/admin/products/${p.id}/link-warehouses`"
                                        class="text-xs text-sky-200 hover:underline"
                                    >
                                        {{ t.products?.link_warehouses }}
                                    </AppLink>
                                    <AppLink :href="`/admin/products/${p.id}/edit`" class="ms-3 text-xs text-emerald-200 hover:underline">
                                        {{ t.products?.edit }}
                                    </AppLink>
                                    <button type="button" class="ms-3 text-xs text-rose-300 hover:underline" @click="destroy(p.id)">
                                        {{ t.products?.delete }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td colspan="10" class="px-4 py-10 text-center text-white/55">{{ t.products?.empty }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="props.products?.links ?? []" />
            </div>
        </div>
    </AdminLayout>
</template>
