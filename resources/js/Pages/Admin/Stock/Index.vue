<script setup>
import { computed, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppLink from '../../../admin/AppLink.vue';
import { useAdminForm } from '../../../admin/useAdminForm';
import { useAdminHead } from '../../../admin/useAdminHead';
import ProductPicker from '../Partials/ProductPicker.vue';
import Toaster from '../Partials/Toaster.vue';
import { fmt, fmtDateTime, localizedName } from '../Partials/helpers';

const props = defineProps({
    warehouseSummaries: { type: Array, default: () => [] },
    totalProducts: { type: Number, default: 0 },
    totalUnits: { type: Number, default: 0 },
    lowStockCount: { type: Number, default: 0 },
    lowStock: { type: Array, default: () => [] },
    recentMovements: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    activeWarehouses: { type: Array, default: () => [] },
    balances: { type: Object, default: () => ({}) },
    translations: { type: Object, default: () => ({}) },
    locale: { type: String, default: 'ar' },
});

const t = computed(() => props.translations?.admin ?? {});
const locale = computed(() => props.locale ?? 'ar');

const activeOp = ref('receive');

const ops = [
    { key: 'receive', labelKey: 'receive' },
    { key: 'dispatch', labelKey: 'dispatch' },
    { key: 'adjust', labelKey: 'adjust' },
    { key: 'transfer', labelKey: 'transfer' },
];

const recvForm = useAdminForm({ product_id: '', warehouse_id: '', quantity: 1, note: '' });
const dispForm = useAdminForm({ product_id: '', warehouse_id: '', quantity: 1, note: '' });
const adjForm = useAdminForm({ product_id: '', warehouse_id: '', quantity: 0, note: '' });
const transForm = useAdminForm({ product_id: '', from_warehouse_id: '', to_warehouse_id: '', quantity: 1, note: '' });

const selectCls =
    'mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white placeholder-white/30 focus:border-emerald-400/40 focus:outline-none';
const inputCls =
    'mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white placeholder-white/30 focus:border-emerald-400/40 focus:outline-none';

useAdminHead(t.value.stock?.title ?? '');

function opForm(key) {
    return { receive: recvForm, dispatch: dispForm, adjust: adjForm, transfer: transForm }[key];
}

function submitReceive() {
    recvForm.post('/admin/stock/receive');
}

function submitDispatch() {
    dispForm.post('/admin/stock/dispatch');
}

function submitAdjust() {
    adjForm.post('/admin/stock/adjust');
}

function submitTransfer() {
    transForm.post('/admin/stock/transfer');
}

function balanceFor(productId, warehouseId) {
    if (!productId || !warehouseId) {
        return null;
    }
    const entry = props.balances?.[String(productId)];
    const current = entry?.by_warehouse?.[String(warehouseId)] ?? 0;

    return { current, total: entry?.total ?? 0 };
}

const liveInfo = computed(() => {
    const form = opForm(activeOp.value);
    const wid = activeOp.value === 'transfer' ? form.from_warehouse_id : form.warehouse_id;

    return balanceFor(form.product_id, wid);
});

const exceedsBalance = computed(() => {
    if (activeOp.value !== 'dispatch' && activeOp.value !== 'transfer') {
        return false;
    }
    const form = opForm(activeOp.value);
    const info = balanceFor(form.product_id, activeOp.value === 'transfer' ? form.from_warehouse_id : form.warehouse_id);

    return !!info && Number(form.quantity) > info.current;
});

function typeLabel(type) {
    const map = t.value.stock?.types ?? {};
    return map[type] ?? type;
}

function dir(tp) {
    return tp === 'in' || tp === 'transfer_in';
}
</script>

<template>
    <AdminLayout :heading="t.stock?.heading ?? ''" :subheading="t.stock?.subheading ?? ''">
        <div class="admin-content">
            <Toaster />

            <div class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.stock?.total_units }}</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-100">{{ fmt(totalUnits) }}</div>
                </div>
                <div class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.stock?.total_products }}</div>
                    <div class="mt-1 text-2xl font-bold text-white">{{ fmt(totalProducts) }}</div>
                </div>
                <div class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.stock?.total_warehouses }}</div>
                    <div class="mt-1 text-2xl font-bold text-white">{{ fmt(warehouseSummaries.length) }}</div>
                </div>
                <AppLink href="/admin/stock/balances" class="admin-card p-4">
                    <div class="text-xs text-white/50">{{ t.stock?.low_stock_count }}</div>
                    <div class="mt-1 text-2xl font-bold text-amber-200">{{ fmt(lowStockCount) }}</div>
                </AppLink>
            </div>

            <div class="admin-card mb-4 p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="text-sm font-semibold text-white">{{ t.stock?.operations }}</div>
                        <p class="mt-0.5 text-xs text-white/55">{{ t.stock?.operations_hint }}</p>
                    </div>
                    <AppLink href="/admin/stock/balances" class="text-xs text-emerald-200 hover:underline">
                        {{ t.stock?.view_balances }}
                    </AppLink>
                </div>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    <button
                        v-for="op in ops"
                        :key="op.key"
                        type="button"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition"
                        :class="activeOp === op.key ? 'bg-emerald-600 text-white shadow-md shadow-emerald-900/25' : 'bg-white/5 text-white/70 hover:bg-white/10'"
                        @click="activeOp = op.key"
                    >
                        {{ t.stock?.[op.labelKey] }}
                    </button>
                </div>

                <div
                    v-if="liveInfo"
                    class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-1 rounded-xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-2.5 text-sm"
                >
                    <span class="text-white/70">{{ t.stock?.live_balance }}</span>
                    <span class="font-bold text-emerald-100" dir="ltr">{{ fmt(liveInfo.current) }}</span>
                    <span class="text-white/45">{{ t.stock?.live_total }}:</span>
                    <span class="font-semibold text-white" dir="ltr">{{ fmt(liveInfo.total) }}</span>
                </div>

                <form
                    v-if="activeOp === 'receive'"
                    class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    @submit.prevent="submitReceive"
                >
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.select_product }}</label>
                        <ProductPicker
                            v-model="recvForm.product_id"
                            :products="products"
                            :placeholder="t.stock?.select_product"
                            :locale="locale"
                            :input-class="inputCls"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.select_warehouse }}</label>
                        <select v-model="recvForm.warehouse_id" required :class="selectCls">
                            <option value="">{{ t.stock?.select_warehouse }}</option>
                            <option v-for="w in activeWarehouses" :key="w.id" :value="w.id">{{ localizedName(w, locale) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.quantity }}</label>
                        <input v-model.number="recvForm.quantity" type="number" min="1" step="1" dir="ltr" required :class="inputCls" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.note_optional }}</label>
                        <input v-model="recvForm.note" type="text" :class="inputCls" />
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <button
                            type="submit"
                            :disabled="recvForm.processing"
                            class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500 disabled:opacity-50"
                        >
                            {{ t.stock?.receive_btn }}
                        </button>
                    </div>
                </form>

                <form
                    v-else-if="activeOp === 'dispatch'"
                    class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    @submit.prevent="submitDispatch"
                >
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.select_product }}</label>
                        <ProductPicker
                            v-model="dispForm.product_id"
                            :products="products"
                            :placeholder="t.stock?.select_product"
                            :locale="locale"
                            :input-class="inputCls"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.select_warehouse }}</label>
                        <select v-model="dispForm.warehouse_id" required :class="selectCls">
                            <option value="">{{ t.stock?.select_warehouse }}</option>
                            <option v-for="w in activeWarehouses" :key="w.id" :value="w.id">{{ localizedName(w, locale) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.quantity }}</label>
                        <input v-model.number="dispForm.quantity" type="number" min="1" step="1" dir="ltr" required :class="inputCls" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.note_optional }}</label>
                        <input v-model="dispForm.note" type="text" :class="inputCls" />
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <p v-if="exceedsBalance" class="mb-2 rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-xs text-rose-200">
                            {{ t.stock?.insufficient_hint }}
                        </p>
                        <button
                            type="submit"
                            :disabled="dispForm.processing"
                            class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500 disabled:opacity-50"
                        >
                            {{ t.stock?.dispatch_btn }}
                        </button>
                    </div>
                </form>

                <form
                    v-else-if="activeOp === 'adjust'"
                    class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    @submit.prevent="submitAdjust"
                >
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.select_product }}</label>
                        <ProductPicker
                            v-model="adjForm.product_id"
                            :products="products"
                            :placeholder="t.stock?.select_product"
                            :locale="locale"
                            :input-class="inputCls"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.select_warehouse }}</label>
                        <select v-model="adjForm.warehouse_id" required :class="selectCls">
                            <option value="">{{ t.stock?.select_warehouse }}</option>
                            <option v-for="w in activeWarehouses" :key="w.id" :value="w.id">{{ localizedName(w, locale) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.adjust_quantity }}</label>
                        <input v-model.number="adjForm.quantity" type="number" min="0" step="1" dir="ltr" required :class="inputCls" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.note_optional }}</label>
                        <input v-model="adjForm.note" type="text" :class="inputCls" />
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <button
                            type="submit"
                            :disabled="adjForm.processing"
                            class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500 disabled:opacity-50"
                        >
                            {{ t.stock?.adjust_btn }}
                        </button>
                    </div>
                </form>

                <form
                    v-else
                    class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    @submit.prevent="submitTransfer"
                >
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.select_product }}</label>
                        <ProductPicker
                            v-model="transForm.product_id"
                            :products="products"
                            :placeholder="t.stock?.select_product"
                            :locale="locale"
                            :input-class="inputCls"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.select_from }}</label>
                        <select v-model="transForm.from_warehouse_id" required :class="selectCls">
                            <option value="">{{ t.stock?.select_from }}</option>
                            <option v-for="w in activeWarehouses" :key="w.id" :value="w.id">{{ localizedName(w, locale) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.select_to }}</label>
                        <select v-model="transForm.to_warehouse_id" required :class="selectCls">
                            <option value="">{{ t.stock?.select_to }}</option>
                            <option v-for="w in activeWarehouses" :key="w.id" :value="w.id">{{ localizedName(w, locale) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.quantity }}</label>
                        <input v-model.number="transForm.quantity" type="number" min="1" step="1" dir="ltr" required :class="inputCls" />
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="block text-xs font-medium text-white/70">{{ t.stock?.note_optional }}</label>
                        <input v-model="transForm.note" type="text" :class="inputCls" />
                    </div>
                    <div class="flex items-end">
                        <button
                            type="submit"
                            :disabled="transForm.processing"
                            class="admin-btn w-full rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500 disabled:opacity-50"
                        >
                            {{ t.stock?.transfer_btn }}
                        </button>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <p v-if="exceedsBalance" class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-xs text-rose-200">
                            {{ t.stock?.insufficient_hint }}
                        </p>
                    </div>
                </form>
            </div>

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-white">{{ t.stock?.warehouses_heading }}</h2>
                <AppLink href="/admin/warehouses" class="text-xs text-emerald-200 hover:underline">{{ t.stock?.manage_warehouses }}</AppLink>
            </div>

            <div v-if="warehouseSummaries.length === 0" class="admin-card mb-4 p-5 text-sm text-white/60">
                {{ t.stock?.no_warehouses }}
            </div>
            <div v-else class="mb-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <AppLink
                    v-for="w in warehouseSummaries"
                    :key="w.id"
                    href="/admin/warehouses"
                    class="admin-card p-5"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-medium text-white">{{ localizedName(w, locale) }}</div>
                            <div v-if="w.code" class="mt-0.5 font-mono text-[11px] text-white/45" dir="ltr">{{ w.code }}</div>
                            <div v-if="w.location" class="mt-1 text-xs text-white/50">{{ w.location }}</div>
                        </div>
                        <span v-if="!w.is_active" class="admin-badge admin-badge--neutral">{{ t.stock?.inactive }}</span>
                    </div>
                    <div class="mt-4 flex items-center gap-6 text-sm">
                        <div>
                            <div class="text-lg font-bold text-emerald-100">{{ fmt(w.total_units) }}</div>
                            <div class="text-[11px] text-white/45">{{ t.stock?.unit_label }}</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-white">{{ fmt(w.product_count) }}</div>
                            <div class="text-[11px] text-white/45">{{ t.stock?.products_in_wh?.replace(':count', fmt(w.product_count)) }}</div>
                        </div>
                    </div>
                </AppLink>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="admin-card p-5">
                    <div class="mb-3 text-sm font-semibold text-white">{{ t.stock?.low_stock_heading }}</div>
                    <ul v-if="lowStock.length" class="space-y-2.5">
                        <li v-for="p in lowStock" :key="p.id" class="flex items-center justify-between gap-3 text-sm">
                            <span class="min-w-0 text-white/80">{{ localizedName(p, locale) }}</span>
                            <span class="flex shrink-0 items-center gap-2">
                                <span class="admin-badge admin-badge--amber">{{ t.stock?.low }}</span>
                                <span class="font-semibold text-white">{{ fmt(p.total_units) }}</span>
                            </span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-white/60">{{ t.stock?.no_low_stock }}</p>
                </div>

                <div class="admin-card p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="text-sm font-semibold text-white">{{ t.stock?.recent_movements }}</div>
                        <AppLink href="/admin/stock/movements" class="text-xs text-emerald-200 hover:underline">{{ t.stock?.view_all }}</AppLink>
                    </div>
                    <ul v-if="recentMovements.length" class="space-y-2.5">
                        <li v-for="m in recentMovements" :key="m.id" class="flex items-center justify-between gap-3 text-sm">
                            <span class="min-w-0 text-white/80">{{ localizedName(m.product, locale) }}</span>
                            <span class="flex shrink-0 items-center gap-2">
                                <span class="admin-badge" :class="dir(m.type) ? 'admin-badge--green' : 'admin-badge--rose'">
                                    {{ typeLabel(m.type) }}
                                </span>
                                <span class="font-semibold text-white" :class="dir(m.type) ? 'text-emerald-200' : 'text-rose-200'">
                                    {{ dir(m.type) ? '+' : 'âˆ’' }}{{ fmt(m.quantity) }}
                                </span>
                                <span class="text-[11px] text-white/40">{{ fmtDateTime(m.occurred_at) }}</span>
                            </span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-white/60">{{ t.stock?.no_movements }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
