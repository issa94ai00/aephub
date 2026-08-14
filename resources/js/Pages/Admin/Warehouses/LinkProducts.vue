<script setup>
import { computed, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppLink from '../../../admin/AppLink.vue';
import { useAdminForm } from '../../../admin/useAdminForm';
import { useAdminHead } from '../../../admin/useAdminHead';
import Alert from '../Partials/Alert.vue';
import { fmt, localizedName, unitLabel } from '../Partials/helpers';

const props = defineProps({
    warehouse: { type: Object, required: true },
    products: { type: Array, default: () => [] },
    translations: { type: Object, default: () => ({}) },
    locale: { type: String, default: 'ar' },
});

const t = computed(() => props.translations?.admin ?? {});
const locale = computed(() => props.locale ?? 'ar');

const form = useAdminForm({
    products: props.products.map((p) => ({
        id: p.id,
        linked: p.linked,
        quantity: p.quantity,
    })),
});

const originalLinked = ref(new Map());
const originalQuantity = ref(new Map());
props.products.forEach((p) => {
    originalLinked.value.set(p.id, p.linked);
    originalQuantity.value.set(p.id, p.quantity);
});

function row(p) {
    return form.products.find((r) => r.id === p.id);
}

function canSetBalance(p) {
    const r = row(p);
    return r.linked && !originalLinked.value.get(p.id);
}

function wouldUnlinkWithStock(p) {
    const r = row(p);
    return !r.linked && originalLinked.value.get(p.id) && originalQuantity.value.get(p.id) > 0;
}

function toggle(p) {
    const r = row(p);
    r.linked = !r.linked;
}

function submit() {
    form.transform((data) => ({
        products: data.products.map(({ id, linked, quantity }) => ({ id, linked, quantity })),
    })).put(`/admin/warehouses/${props.warehouse.id}/link-products`);
}

const subheading = computed(() =>
    (t.value.warehouses?.link_products_sub ?? '').replace(':name', props.warehouse?.name ?? '')
);

useAdminHead(t.value.warehouses?.link_products_title ?? '');
</script>

<template>
    <AdminLayout :heading="t.warehouses?.link_products_heading ?? ''" :subheading="subheading">
        <div class="admin-content">
            <Alert />

            <div class="mb-4">
                <AppLink href="/admin/warehouses" class="inline-flex items-center gap-1 text-xs text-emerald-200 hover:underline">
                    <span aria-hidden="true">&larr;</span>
                    {{ t.warehouses?.back_list }}
                </AppLink>
            </div>

            <form @submit.prevent="submit">
                <div class="mb-5 grid gap-4 md:grid-cols-4">
                    <div class="admin-card p-5 md:col-span-2">
                        <div class="text-xs uppercase tracking-wide text-white/45">{{ t.warehouses?.col_name }}</div>
                        <div class="mt-1 text-lg font-semibold text-white">{{ localizedName(warehouse, locale) }}</div>
                        <div v-if="warehouse.name_en && locale === 'ar'" class="mt-0.5 text-xs text-white/45" dir="ltr">
                            {{ warehouse.name_en }}
                        </div>
                    </div>
                    <div class="admin-card p-5">
                        <div class="text-xs uppercase tracking-wide text-white/45">{{ t.warehouses?.col_code }}</div>
                        <div class="mt-1 font-mono text-sm text-emerald-100/90" dir="ltr">{{ warehouse.code || 'â€”' }}</div>
                        <div v-if="warehouse.location" class="mt-2 text-xs text-white/45">{{ warehouse.location }}</div>
                    </div>
                    <div class="admin-card p-5">
                        <div class="text-xs uppercase tracking-wide text-white/45">{{ t.warehouses?.col_units }}</div>
                        <div class="mt-1 text-lg font-semibold text-white">{{ fmt(warehouse.total_units) }}</div>
                        <div class="mt-2 text-xs text-white/45">{{ t.warehouses?.col_products }}: {{ products.length }}</div>
                    </div>
                </div>

                <div class="admin-card mb-5 p-5">
                    <p class="text-sm leading-relaxed text-white/65">{{ t.warehouses?.link_products_intro }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="p in products"
                        :key="p.id"
                        class="admin-card p-5"
                        :class="{ '!border-emerald-400/40': row(p)?.linked }"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-white">{{ localizedName(p, locale) }}</span>
                                    <span
                                        v-if="p.sku"
                                        class="rounded-md border border-white/10 bg-white/5 px-1.5 py-0.5 font-mono text-[10px] text-emerald-100/80"
                                        dir="ltr"
                                    >
                                        {{ p.sku }}
                                    </span>
                                </div>
                                <div class="mt-0.5 text-xs text-white/50">{{ unitLabel(p.unit, t.value) }}</div>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="row(p)?.linked"
                                class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors"
                                :class="row(p)?.linked ? 'bg-emerald-500' : 'bg-white/15'"
                                @click="toggle(p)"
                            >
                                <span
                                    class="inline-block h-3.5 w-3.5 rounded-full bg-white shadow transition-transform"
                                    :class="row(p)?.linked ? 'translate-x-[1.1rem]' : 'translate-x-0.5'"
                                ></span>
                            </button>
                        </div>

                        <div class="mt-4">
                            <template v-if="canSetBalance(p)">
                                <label class="block text-xs font-medium text-white/70">{{ t.warehouses?.initial_balance }}</label>
                                <input
                                    v-model.number="row(p).quantity"
                                    type="number"
                                    min="0"
                                    step="1"
                                    dir="ltr"
                                    class="mt-1.5 w-full rounded-xl border border-emerald-400/30 bg-[#0a0f0d] px-3 py-2 text-sm text-white focus:border-emerald-400/50 focus:outline-none"
                                />
                                <p class="mt-1 text-[11px] text-white/40">{{ t.warehouses?.linked }} â€” {{ t.warehouses?.initial_balance }}</p>
                            </template>
                            <template v-else>
                                <div class="text-xs text-white/50">
                                    <span class="font-semibold text-white/80">{{ fmt(originalQuantity.get(p.id)) }}</span>
                                    {{ t.warehouses?.current_balance }}
                                </div>
                            </template>
                        </div>

                        <div v-if="wouldUnlinkWithStock(p)" class="mt-3 rounded-xl border border-amber-400/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-100">
                            {{ t.warehouses?.unlink_has_stock }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500 disabled:opacity-50"
                    >
                        {{ t.warehouses?.link_save }}
                    </button>
                    <AppLink
                        href="/admin/warehouses"
                        class="rounded-xl border border-white/15 px-5 py-2.5 text-sm text-white/80 hover:bg-white/5"
                    >
                        {{ t.warehouses?.link_cancel }}
                    </AppLink>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
