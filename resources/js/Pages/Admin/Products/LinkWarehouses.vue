<script setup>
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppLink from '../../../Admin/AppLink.vue';
import { useAdminForm } from '../../../Admin/useAdminForm';
import { useAdminHead } from '../../../Admin/useAdminHead';
import Alert from '../Partials/Alert.vue';
import { fmt, localizedName, unitLabel } from '../Partials/helpers';

const props = defineProps({
    product: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
    translations: { type: Object, default: () => ({}) },
    locale: { type: String, default: 'ar' },
});

const t = computed(() => props.translations?.admin ?? {});
const locale = computed(() => props.locale ?? 'ar');

const form = useAdminForm({
    warehouses: props.warehouses.map((w) => ({
        id: w.id,
        linked: w.linked,
        quantity: w.quantity,
    })),
});

const originalLinked = computed(() => new Map(props.warehouses.map((w) => [w.id, w.linked])));
const originalQuantity = computed(() => new Map(props.warehouses.map((w) => [w.id, w.quantity])));

function row(w) {
    return form.warehouses.find((r) => r.id === w.id);
}

function canSetBalance(w) {
    const r = row(w);
    return r.linked && !originalLinked.value.get(w.id);
}

function wouldUnlinkWithStock(w) {
    const r = row(w);
    return !r.linked && originalLinked.value.get(w.id) && originalQuantity.value.get(w.id) > 0;
}

function toggle(w) {
    const r = row(w);
    r.linked = !r.linked;
}

function submit() {
    form.transform((data) => ({
        warehouses: data.warehouses.map(({ id, linked, quantity }) => ({ id, linked, quantity })),
    })).put(`/admin/products/${props.product.id}/link-warehouses`);
}

const subheading = computed(() =>
    (t.value.products?.link_warehouses_sub ?? '').replace(':name', props.product?.name ?? '')
);

useAdminHead(t.value.products?.link_warehouses_title ?? '');
</script>

<template>
    <AdminLayout :heading="t.products?.link_warehouses_heading ?? ''" :subheading="subheading">
        <div class="admin-content">
            <Alert />

            <div class="mb-4">
                <AppLink href="/admin/products" class="inline-flex items-center gap-1 text-xs text-emerald-200 hover:underline">
                    <span aria-hidden="true">&larr;</span>
                    {{ t.products?.back_list }}
                </AppLink>
            </div>

            <form @submit.prevent="submit">
                <div class="mb-5 grid gap-4 md:grid-cols-3">
                    <div class="admin-card p-5">
                        <div class="text-xs uppercase tracking-wide text-white/45">{{ t.products?.col_sku }}</div>
                        <div class="mt-1 font-mono text-sm text-emerald-100/90" dir="ltr">{{ product.sku || '—' }}</div>
                    </div>
                    <div class="admin-card p-5 md:col-span-2">
                        <div class="text-xs uppercase tracking-wide text-white/45">{{ t.products?.col_name }}</div>
                        <div class="mt-1 text-lg font-semibold text-white">{{ localizedName(product, locale) }}</div>
                        <div v-if="product.name_en && locale === 'ar'" class="mt-0.5 text-xs text-white/45" dir="ltr">
                            {{ product.name_en }}
                        </div>
                    </div>
                </div>

                <div class="admin-card mb-5 p-5">
                    <p class="text-sm leading-relaxed text-white/65">{{ t.products?.link_warehouses_intro }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="w in warehouses"
                        :key="w.id"
                        class="admin-card p-5"
                        :class="{ '!border-emerald-400/40': row(w)?.linked }"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-medium text-white">{{ localizedName(w, locale) }}</div>
                                <div v-if="w.code" class="mt-0.5 font-mono text-[11px] text-white/45" dir="ltr">{{ w.code }}</div>
                                <div v-if="w.location" class="mt-1 text-xs text-white/50">{{ w.location }}</div>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="row(w)?.linked"
                                class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors"
                                :class="row(w)?.linked ? 'bg-emerald-500' : 'bg-white/15'"
                                @click="toggle(w)"
                            >
                                <span
                                    class="inline-block h-3.5 w-3.5 rounded-full bg-white shadow transition-transform"
                                    :class="row(w)?.linked ? 'translate-x-[1.1rem]' : 'translate-x-0.5'"
                                ></span>
                            </button>
                        </div>

                        <div class="mt-4">
                            <template v-if="canSetBalance(w)">
                                <label class="block text-xs font-medium text-white/70">{{ t.products?.initial_balance }}</label>
                                <input
                                    v-model.number="row(w).quantity"
                                    type="number"
                                    min="0"
                                    step="1"
                                    dir="ltr"
                                    class="mt-1.5 w-full rounded-xl border border-emerald-400/30 bg-[#0a0f0d] px-3 py-2 text-sm text-white focus:border-emerald-400/50 focus:outline-none"
                                />
                                <p class="mt-1 text-[11px] text-white/40">{{ t.products?.linked }} — {{ t.products?.initial_balance }}</p>
                            </template>
                            <template v-else>
                                <div class="text-xs text-white/50">
                                    <span class="font-semibold text-white/80">{{ fmt(originalQuantity.get(w.id)) }}</span>
                                    {{ t.products?.current_balance }}
                                </div>
                            </template>
                        </div>

                        <div v-if="wouldUnlinkWithStock(w)" class="mt-3 rounded-xl border border-amber-400/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-100">
                            {{ t.products?.unlink_has_stock }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500 disabled:opacity-50"
                    >
                        {{ t.products?.link_save }}
                    </button>
                    <AppLink
                        href="/admin/products"
                        class="rounded-xl border border-white/15 px-5 py-2.5 text-sm text-white/80 hover:bg-white/5"
                    >
                        {{ t.products?.link_cancel }}
                    </AppLink>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
