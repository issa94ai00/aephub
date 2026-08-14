<script setup>
import { computed, reactive, ref, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppLink from '../../../admin/AppLink.vue';
import { router } from '../../../admin/adminRouter';
import { useAdminForm } from '../../../admin/useAdminForm';
import { useAdminHead } from '../../../admin/useAdminHead';
import Toaster from '../Partials/Toaster.vue';
import Pagination from '../Partials/Pagination.vue';
import { fmt, localizedName, unitLabel } from '../Partials/helpers';

const props = defineProps({
    products: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
    search: { type: String, default: '' },
    stats: { type: Object, default: () => ({}) },
    translations: { type: Object, default: () => ({}) },
    locale: { type: String, default: 'ar' },
});

const t = computed(() => props.translations?.admin ?? {});
const locale = computed(() => props.locale ?? 'ar');

const searchInput = ref(props.search ?? '');

const drafts = reactive({});

const matrix = computed(() =>
    (props.products?.data ?? []).map((p) => ({
        product: p,
        cells: props.warehouses.map((w) => {
            const link = (p.links ?? []).find((l) => l.warehouse_id === w.id);
            const originalLinked = !!link;
            const originalQty = link ? link.quantity : 0;
            const key = `${p.id}:${w.id}`;
            const d = drafts[key];
            return {
                key,
                w,
                originalLinked,
                originalQty,
                linked: d ? d.checked : originalLinked,
                initial: d ? d.initial : originalQty,
                blocked: originalLinked && originalQty > 0,
            };
        }),
    }))
);

watch(
    () => props.products?.data,
    () => {
        Object.keys(drafts).forEach((k) => delete drafts[k]);
    }
);

const form = useAdminForm({ rows: [] });

function toggle(c) {
    if (c.blocked) {
        return;
    }
    const d = drafts[c.key];
    drafts[c.key] = {
        checked: !(d ? d.checked : c.originalLinked),
        initial: d ? d.initial : c.originalQty,
    };
}

function setInitial(c, value) {
    const n = Math.max(0, parseInt(String(value || '0'), 10) || 0);
    drafts[c.key] = { checked: c.linked, initial: n };
}

function buildRows() {
    return matrix.value.map((row) => ({
        product_id: row.product.id,
        links: row.cells.map((c) => {
            const d = drafts[c.key];
            const linked = d ? d.checked : c.originalLinked;
            const quantity = linked ? (d ? d.initial : c.originalQty) : c.originalQty;
            return { warehouse_id: c.w.id, linked, quantity };
        }),
    }));
}

function submit() {
    form.rows = buildRows();
    form.post('/admin/stock/organize');
}

function apply() {
    const query = {};
    if (searchInput.value) {
        query.search = searchInput.value;
    }
    router.replace({ path: '/admin/stock/organize', query });
}

function clearSearch() {
    searchInput.value = '';
    router.replace('/admin/stock/organize');
}

useAdminHead(t.value.stock?.organize_title ?? '');
</script>

<template>
    <AdminLayout :heading="t.stock?.organize_heading ?? ''" :subheading="t.stock?.organize_sub ?? ''">
        <div class="admin-content">
            <Toaster />

            <div class="mb-4">
                <AppLink href="/admin/stock" class="inline-flex items-center gap-1 text-xs text-emerald-200 hover:underline">
                    <span aria-hidden="true">&larr;</span>
                    {{ t.stock?.back_stock }}
                </AppLink>
            </div>

            <div class="mb-5 grid gap-4 sm:grid-cols-3">
                <div class="admin-card p-5">
                    <div class="text-xs uppercase tracking-wide text-white/45">{{ t.stock?.total_products }}</div>
                    <div class="mt-1 text-lg font-semibold text-white">{{ fmt(stats.products) }}</div>
                </div>
                <div class="admin-card p-5">
                    <div class="text-xs uppercase tracking-wide text-white/45">{{ t.stock?.total_units }}</div>
                    <div class="mt-1 text-lg font-semibold text-white">{{ fmt(stats.units) }}</div>
                </div>
                <div class="admin-card p-5">
                    <div class="text-xs uppercase tracking-wide text-white/45">{{ t.stock?.total_warehouses }}</div>
                    <div class="mt-1 text-lg font-semibold text-white">{{ fmt(stats.warehouses) }}</div>
                </div>
            </div>

            <div class="admin-card mb-5 p-5">
                <p class="text-sm leading-relaxed text-white/65">{{ t.stock?.organize_intro }}</p>
            </div>

            <form @submit.prevent="apply" class="mb-4 flex flex-wrap items-end gap-2">
                <div class="min-w-64 flex-1">
                    <input
                        v-model="searchInput"
                        type="search"
                        :placeholder="t.stock?.organize_search_placeholder"
                        class="w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white placeholder-white/35 focus:border-emerald-400/40 focus:outline-none"
                    />
                </div>
                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="admin-btn rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white/80 hover:bg-white/10"
                    >
                        {{ t.stock?.apply }}
                    </button>
                    <button v-if="searchInput" type="button" @click="clearSearch" class="text-xs text-white/50 hover:text-emerald-200">
                        {{ t.stock?.clear }}
                    </button>
                </div>
            </form>

            <form @submit.prevent="submit" class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                            <tr>
                                <th class="px-4 py-3 text-start">{{ t.stock?.col_product }}</th>
                                <th v-for="w in warehouses" :key="w.id" class="px-4 py-3 text-center">
                                    <div>{{ localizedName(w, locale) }}</div>
                                    <div v-if="w.code" class="mt-0.5 font-mono text-[10px] text-white/35" dir="ltr">{{ w.code }}</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="row in matrix" :key="row.product.id" class="hover:bg-white/[0.02]">
                                <td class="max-w-56 px-4 py-3 align-top">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-white">{{ localizedName(row.product, locale) }}</span>
                                        <span
                                            v-if="row.product.sku"
                                            class="rounded-md border border-white/10 bg-white/5 px-1.5 py-0.5 font-mono text-[10px] text-emerald-100/80"
                                            dir="ltr"
                                        >
                                            {{ row.product.sku }}
                                        </span>
                                    </div>
                                    <div class="mt-1 text-xs text-white/45">{{ unitLabel(row.product.unit, t) }}</div>
                                </td>
                                <td v-for="c in row.cells" :key="c.key" class="px-4 py-3 align-top">
                                    <div class="flex flex-col items-center gap-1.5">
                                        <button
                                            type="button"
                                            role="switch"
                                            :aria-checked="c.linked"
                                            :disabled="c.blocked"
                                            class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                                            :class="c.linked ? 'bg-emerald-500' : 'bg-white/15'"
                                            @click="toggle(c)"
                                        >
                                            <span
                                                class="inline-block h-3.5 w-3.5 rounded-full bg-white shadow transition-transform"
                                                :class="c.linked ? 'translate-x-[1.1rem]' : 'translate-x-0.5'"
                                            ></span>
                                        </button>

                                        <template v-if="c.linked && !c.originalLinked">
                                            <input
                                                :value="c.initial"
                                                type="number"
                                                min="0"
                                                step="1"
                                                dir="ltr"
                                                class="w-20 rounded-lg border border-emerald-400/30 bg-[#0a0f0d] px-2 py-1 text-center text-sm text-white focus:border-emerald-400/50 focus:outline-none"
                                                @input="setInitial(c, $event.target.value)"
                                            />
                                            <span class="text-[10px] text-white/40">{{ t.stock?.organize_initial_balance }}</span>
                                        </template>
                                        <template v-else-if="c.linked">
                                            <div class="flex flex-col items-center">
                                                <span class="text-sm font-semibold text-emerald-100" dir="ltr">{{ fmt(c.originalQty) }}</span>
                                                <span class="text-[10px] text-white/40">{{ t.stock?.organize_balance }}</span>
                                            </div>
                                        </template>
                                        <span v-else class="text-[10px] text-white/30">{{ t.stock?.organize_not_linked }}</span>
                                    </div>
                                    <p v-if="c.blocked" class="mt-1 text-center text-[10px] leading-snug text-amber-200/80">
                                        {{ t.stock?.organize_unlink_blocked }}
                                    </p>
                                </td>
                            </tr>
                            <tr v-if="matrix.length === 0">
                                <td :colspan="warehouses.length + 1" class="px-4 py-10 text-center text-white/55">
                                    {{ t.stock?.organize_empty }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-white/10 px-4 py-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500 disabled:opacity-50"
                    >
                        {{ t.stock?.organize_save }}
                    </button>
                    <Pagination :links="props.products?.links ?? []" />
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
