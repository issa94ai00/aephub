<script setup>
import { computed, reactive } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppLink from '../../../Admin/AppLink.vue';
import { router } from '../../../Admin/adminRouter';
import { useAdminHead } from '../../../Admin/useAdminHead';
import Toaster from '../Partials/Toaster.vue';
import Pagination from '../Partials/Pagination.vue';
import { fmt, fmtDateTime, localizedName } from '../Partials/helpers';

const props = defineProps({
    movements: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    type: { type: String, default: null },
    warehouseId: { type: String, default: null },
    productId: { type: String, default: null },
    from: { type: String, default: null },
    to: { type: String, default: null },
    translations: { type: Object, default: () => ({}) },
    locale: { type: String, default: 'ar' },
});

const t = computed(() => props.translations?.admin ?? {});
const locale = computed(() => props.locale ?? 'ar');

const rows = computed(() => props.movements?.data ?? []);

const filters = reactive({
    type: props.type ?? '',
    warehouse_id: props.warehouseId ?? '',
    product_id: props.productId ?? '',
    from: props.from ?? '',
    to: props.to ?? '',
});

const hasFilters = computed(() =>
    filters.type !== '' || filters.warehouse_id !== '' || filters.product_id !== '' || filters.from !== '' || filters.to !== ''
);

useAdminHead(t.value.stock?.movements_title ?? '');

function apply() {
    const query = {};
    if (filters.type) {
        query.type = filters.type;
    }
    if (filters.warehouse_id) {
        query.warehouse_id = filters.warehouse_id;
    }
    if (filters.product_id) {
        query.product_id = filters.product_id;
    }
    if (filters.from) {
        query.from = filters.from;
    }
    if (filters.to) {
        query.to = filters.to;
    }
    router.replace({ path: '/admin/stock/movements', query });
}

function clearFilters() {
    filters.type = '';
    filters.warehouse_id = '';
    filters.product_id = '';
    filters.from = '';
    filters.to = '';
    router.replace('/admin/stock/movements');
}

function exportUrl() {
    const q = new URLSearchParams();
    if (filters.type) {
        q.set('type', filters.type);
    }
    if (filters.warehouse_id) {
        q.set('warehouse_id', filters.warehouse_id);
    }
    if (filters.product_id) {
        q.set('product_id', filters.product_id);
    }
    if (filters.from) {
        q.set('from', filters.from);
    }
    if (filters.to) {
        q.set('to', filters.to);
    }
    const s = q.toString();

    return `/admin/stock/movements/export${s ? `?${s}` : ''}`;
}

function typeLabel(type) {
    const map = t.value.stock?.types ?? {};
    return map[type] ?? type;
}

function dir(tp) {
    return tp === 'in' || tp === 'transfer_in';
}

function typeOptions() {
    const types = t.value.stock?.types ?? {};
    return Object.keys(types).map((k) => ({ value: k, label: types[k] }));
}

const inputCls =
    'rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white placeholder-white/35 focus:border-emerald-400/40 focus:outline-none';
</script>

<template>
    <AdminLayout :heading="t.stock?.movements_heading ?? ''" :subheading="t.stock?.movements_sub ?? ''">
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

            <form @submit.prevent="apply" class="mb-4 flex flex-wrap items-end gap-2">
                <div>
                    <label class="block text-[11px] text-white/50">{{ t.stock?.filter_type }}</label>
                    <select v-model="filters.type" class="mt-1 " :class="inputCls">
                        <option value="">{{ t.stock?.filter_all }}</option>
                        <option v-for="o in typeOptions()" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] text-white/50">{{ t.stock?.filter_warehouse }}</label>
                    <select v-model="filters.warehouse_id" class="mt-1" :class="inputCls">
                        <option value="">{{ t.stock?.filter_all }}</option>
                        <option v-for="w in warehouses" :key="w.id" :value="String(w.id)">{{ localizedName(w, locale) }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] text-white/50">{{ t.stock?.filter_product }}</label>
                    <select v-model="filters.product_id" class="mt-1" :class="inputCls">
                        <option value="">{{ t.stock?.filter_all }}</option>
                        <option v-for="p in products" :key="p.id" :value="String(p.id)">{{ localizedName(p, locale) }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] text-white/50">{{ t.stock?.filter_from }}</label>
                    <input v-model="filters.from" type="date" dir="ltr" class="mt-1" :class="inputCls" />
                </div>
                <div>
                    <label class="block text-[11px] text-white/50">{{ t.stock?.filter_to }}</label>
                    <input v-model="filters.to" type="date" dir="ltr" class="mt-1" :class="inputCls" />
                </div>
                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="admin-btn rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white/80 hover:bg-white/10"
                    >
                        {{ t.stock?.apply }}
                    </button>
                    <button v-if="hasFilters" type="button" @click="clearFilters" class="text-xs text-white/50 hover:text-emerald-200">
                        {{ t.stock?.clear }}
                    </button>
                </div>
            </form>

            <div class="admin-card mb-4 p-4 text-sm text-white/60">
                {{ t.stock?.movements_title }}: <span class="font-semibold text-white">{{ fmt(props.movements?.meta?.total ?? rows.length) }}</span>
            </div>

            <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                            <tr>
                                <th class="px-4 py-3 text-start">#</th>
                                <th class="px-4 py-3 text-start">{{ t.stock?.col_time }}</th>
                                <th class="px-4 py-3 text-start">{{ t.stock?.col_type }}</th>
                                <th class="px-4 py-3 text-start">{{ t.stock?.col_product }}</th>
                                <th class="px-4 py-3 text-start">{{ t.stock?.col_warehouse }}</th>
                                <th class="px-4 py-3 text-center">{{ t.stock?.col_qty }}</th>
                                <th class="px-4 py-3 text-center">{{ t.stock?.col_balance }}</th>
                                <th class="px-4 py-3 text-start">{{ t.stock?.col_by }}</th>
                                <th class="px-4 py-3 text-start">{{ t.stock?.col_note }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="m in rows" :key="m.id" class="hover:bg-white/[0.02]">
                                <td class="px-4 py-3 text-white/60">{{ m.id }}</td>
                                <td class="px-4 py-3 text-xs text-white/55">{{ fmtDateTime(m.occurred_at) }}</td>
                                <td class="px-4 py-3">
                                    <span class="admin-badge" :class="dir(m.type) ? 'admin-badge--green' : 'admin-badge--rose'">
                                        {{ typeLabel(m.type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-white">
                                    <div class="font-medium">{{ localizedName(m.product, locale) }}</div>
                                    <div v-if="m.product?.sku" class="mt-0.5 font-mono text-[11px] text-white/40" dir="ltr">{{ m.product.sku }}</div>
                                </td>
                                <td class="px-4 py-3 text-white/70">{{ localizedName(m.warehouse, locale) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-semibold" :class="dir(m.type) ? 'text-emerald-200' : 'text-rose-200'">
                                        {{ dir(m.type) ? '+' : '−' }}{{ fmt(m.quantity) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-white">{{ fmt(m.balance_after) }}</td>
                                <td class="px-4 py-3 text-white/60">{{ m.user?.name ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-white/50">{{ m.note || '—' }}</td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td colspan="9" class="px-4 py-10 text-center text-white/55">{{ t.stock?.no_movements }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="props.movements?.links ?? []" />
            </div>
        </div>
    </AdminLayout>
</template>
