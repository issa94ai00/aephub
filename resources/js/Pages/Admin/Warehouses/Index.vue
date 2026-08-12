<script setup>
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppLink from '../../../Admin/AppLink.vue';
import { useAdminForm } from '../../../Admin/useAdminForm';
import { useAdminHead } from '../../../Admin/useAdminHead';
import Alert from '../Partials/Alert.vue';
import Pagination from '../Partials/Pagination.vue';
import { fmt, localizedName } from '../Partials/helpers';

const props = defineProps({
    warehouses: { type: Object, required: true },
    status: { type: String, default: null },
    stats: { type: Object, default: () => ({}) },
    translations: { type: Object, default: () => ({}) },
    locale: { type: String, default: 'ar' },
});

const t = computed(() => props.translations?.admin ?? {});
const locale = computed(() => props.locale ?? 'ar');

const rows = computed(() => props.warehouses?.data ?? []);

const del = useAdminForm({});

useAdminHead(t.value.warehouses?.title ?? '');

function chipClass(target) {
    return props.status === target
        ? 'bg-emerald-500/20 text-emerald-100 ring-1 ring-emerald-400/20'
        : 'bg-white/5 text-white/70 hover:bg-white/10';
}

function destroy(id) {
    if (!window.confirm(t.value.warehouses?.confirm_delete)) {
        return;
    }
    del.delete(`/admin/warehouses/${id}`);
}
</script>

<template>
    <AdminLayout
        :heading="t.warehouses?.heading ?? ''"
        :subheading="t.warehouses?.subheading ?? ''"
    >
        <div class="admin-content">
            <Alert />

            <div class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <AppLink href="/admin/warehouses" class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.warehouses?.stat_total }}</div>
                    <div class="mt-1 text-2xl font-bold text-white">{{ fmt(stats.warehouses) }}</div>
                </AppLink>
                <AppLink href="/admin/products" class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.warehouses?.stat_products }}</div>
                    <div class="mt-1 text-2xl font-bold text-white">{{ fmt(stats.products) }}</div>
                </AppLink>
                <AppLink href="/admin/stock" class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.warehouses?.stat_units }}</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-100">{{ fmt(stats.units) }}</div>
                </AppLink>
                <AppLink href="/admin/products?low=1" class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.warehouses?.stat_low }}</div>
                    <div class="mt-1 text-2xl font-bold text-amber-200">{{ fmt(stats.low) }}</div>
                </AppLink>
            </div>

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <AppLink :href="'/admin/warehouses'" class="rounded-full px-3 py-1" :class="chipClass('')">
                        {{ t.warehouses?.all }}
                    </AppLink>
                    <AppLink :href="'/admin/warehouses?status=active'" class="rounded-full px-3 py-1" :class="chipClass('active')">
                        {{ t.warehouses?.active }}
                    </AppLink>
                    <AppLink :href="'/admin/warehouses?status=inactive'" class="rounded-full px-3 py-1" :class="chipClass('inactive')">
                        {{ t.warehouses?.inactive }}
                    </AppLink>
                </div>
                <AppLink
                    href="/admin/warehouses/create"
                    class="admin-btn inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-900/25 hover:bg-emerald-500"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t.warehouses?.new }}
                </AppLink>
            </div>

            <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                            <tr>
                                <th class="px-4 py-3 text-start">#</th>
                                <th class="px-4 py-3 text-start">{{ t.warehouses?.col_name }}</th>
                                <th class="px-4 py-3 text-start">{{ t.warehouses?.col_code }}</th>
                                <th class="px-4 py-3 text-start">{{ t.warehouses?.col_location }}</th>
                                <th class="px-4 py-3 text-center">{{ t.warehouses?.col_products }}</th>
                                <th class="px-4 py-3 text-center">{{ t.warehouses?.col_units }}</th>
                                <th class="px-4 py-3 text-start">{{ t.warehouses?.linked_products }}</th>
                                <th class="px-4 py-3 text-center">{{ t.warehouses?.col_status }}</th>
                                <th class="px-4 py-3 text-end">{{ t.warehouses?.col_actions }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="w in rows" :key="w.id" class="hover:bg-white/[0.02]">
                                <td class="px-4 py-3 text-white/60">{{ w.id }}</td>
                                <td class="px-4 py-3 text-white">
                                    <div class="font-medium">{{ localizedName(w, locale) }}</div>
                                    <div v-if="w.name_en && locale === 'ar'" class="mt-0.5 text-xs text-white/45" dir="ltr">
                                        {{ w.name_en }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="w.code"
                                        class="rounded-md border border-white/10 bg-white/5 px-2 py-0.5 font-mono text-xs text-emerald-100/90"
                                        dir="ltr"
                                    >
                                        {{ w.code }}
                                    </span>
                                    <span v-else class="text-white/35">—</span>
                                </td>
                                <td class="px-4 py-3 text-white/60">{{ w.location || '—' }}</td>
                                <td class="px-4 py-3 text-center text-white/70">{{ w.product_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-white">{{ fmt(w.total_units) }}</td>
                                <td class="px-4 py-3">
                                    <div v-if="w.products && w.products.length" class="flex flex-wrap gap-1">
                                        <span
                                            v-for="p in w.products"
                                            :key="p.id"
                                            class="admin-badge admin-badge--violet"
                                            :title="p.sku || localizedName(p, locale)"
                                        >
                                            {{ localizedName(p, locale) }} · {{ fmt(p.quantity) }}
                                        </span>
                                    </div>
                                    <span v-else class="text-xs text-white/40">{{ t.warehouses?.no_links }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="w.is_active" class="admin-badge admin-badge--green">{{ t.warehouses?.active }}</span>
                                    <span v-else class="admin-badge admin-badge--rose">{{ t.warehouses?.inactive }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-end">
                                    <AppLink
                                        :href="`/admin/warehouses/${w.id}/link-products`"
                                        class="text-xs text-sky-200 hover:underline"
                                    >
                                        {{ t.warehouses?.manage_products }}
                                    </AppLink>
                                    <AppLink :href="`/admin/warehouses/${w.id}/edit`" class="ms-3 text-xs text-emerald-200 hover:underline">
                                        {{ t.warehouses?.edit }}
                                    </AppLink>
                                    <button type="button" class="ms-3 text-xs text-rose-300 hover:underline" @click="destroy(w.id)">
                                        {{ t.warehouses?.delete }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td colspan="9" class="px-4 py-10 text-center text-white/55">{{ t.warehouses?.empty }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="props.warehouses?.links ?? []" />
            </div>
        </div>
    </AdminLayout>
</template>
