<script setup>
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppLink from '../../../admin/AppLink.vue';
import { useAdminForm } from '../../../admin/useAdminForm';
import { useAdminHead } from '../../../admin/useAdminHead';
import Alert from '../Partials/Alert.vue';

const props = defineProps({
    product: { type: Object, default: null },
    translations: { type: Object, default: () => ({}) },
});

const t = computed(() => props.translations?.admin ?? {});

const isEdit = computed(() => props.product !== null && props.product !== undefined);

const form = useAdminForm({
    name: props.product?.name ?? '',
    name_en: props.product?.name_en ?? '',
    sku: props.product?.sku ?? '',
    category: props.product?.category ?? '',
    unit: props.product?.unit ?? 'piece',
    min_stock: props.product?.min_stock ?? 0,
    cost_price: props.product?.cost_price ?? '',
    sale_price: props.product?.sale_price ?? '',
    is_active: props.product?.is_active ?? true,
});

const heading = computed(() =>
    isEdit.value ? t.value.products?.edit_heading : t.value.products?.create_heading
);
const subheading = computed(() =>
    isEdit.value
        ? (t.value.products?.edit_sub ?? '').replace(':name', props.product?.name ?? '')
        : t.value.products?.create_sub
);

useAdminHead(isEdit.value ? t.value.products?.edit_title : t.value.products?.create_title);

const inputCls =
    'mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white placeholder-white/30 focus:border-emerald-400/40 focus:outline-none';

function submit() {
    if (isEdit.value) {
        form.put(`/admin/products/${props.product.id}`);
    } else {
        form.post('/admin/products');
    }
}
</script>

<template>
    <AdminLayout :heading="heading" :subheading="subheading">
        <div class="admin-content">
            <Alert />

            <div class="mb-4">
                <AppLink href="/admin/products" class="inline-flex items-center gap-1 text-xs text-emerald-200 hover:underline">
                    <span aria-hidden="true">&larr;</span>
                    {{ t.products?.back_list }}
                </AppLink>
            </div>

            <form @submit.prevent="submit" class="max-w-2xl space-y-5">
                <div class="admin-card space-y-5 p-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-white/70">{{ t.products?.name }}</label>
                            <input v-model="form.name" required :class="inputCls" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-300">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-white/70">{{ t.products?.name_en }}</label>
                            <input v-model="form.name_en" :class="inputCls" dir="ltr" />
                            <p v-if="form.errors.name_en" class="mt-1 text-xs text-rose-300">{{ form.errors.name_en }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-white/70">{{ t.products?.sku }}</label>
                            <input v-model="form.sku" :class="inputCls" dir="ltr" />
                            <p v-if="form.errors.sku" class="mt-1 text-xs text-rose-300">{{ form.errors.sku }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-white/70">{{ t.products?.category }}</label>
                            <input v-model="form.category" :class="inputCls" />
                            <p v-if="form.errors.category" class="mt-1 text-xs text-rose-300">{{ form.errors.category }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-white/70">{{ t.products?.unit }}</label>
                            <select v-model="form.unit" :class="inputCls">
                                <option value="piece">{{ t.units?.piece }}</option>
                                <option value="box">{{ t.units?.box }}</option>
                                <option value="pack">{{ t.units?.pack }}</option>
                                <option value="kg">{{ t.units?.kg }}</option>
                            </select>
                            <p v-if="form.errors.unit" class="mt-1 text-xs text-rose-300">{{ form.errors.unit }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-white/70">{{ t.products?.min_stock }}</label>
                            <input v-model.number="form.min_stock" type="number" min="0" step="1" dir="ltr" :class="inputCls" />
                            <p v-if="form.errors.min_stock" class="mt-1 text-xs text-rose-300">{{ form.errors.min_stock }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-white/70">{{ t.products?.cost_price }}</label>
                            <input v-model="form.cost_price" type="number" min="0" step="0.01" dir="ltr" :class="inputCls" />
                            <p v-if="form.errors.cost_price" class="mt-1 text-xs text-rose-300">{{ form.errors.cost_price }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-white/70">{{ t.products?.sale_price }}</label>
                            <input v-model="form.sale_price" type="number" min="0" step="0.01" dir="ltr" :class="inputCls" />
                            <p v-if="form.errors.sale_price" class="mt-1 text-xs text-rose-300">{{ form.errors.sale_price }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="inline-flex cursor-pointer items-center gap-2.5 text-sm text-white/80">
                            <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded accent-emerald-500" />
                            {{ t.products?.is_active }}
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500 disabled:opacity-50"
                    >
                        {{ isEdit ? t.products?.save : t.products?.create_btn }}
                    </button>
                    <AppLink
                        href="/admin/products"
                        class="rounded-xl border border-white/15 px-5 py-2.5 text-sm text-white/80 hover:bg-white/5"
                    >
                        {{ t.products?.cancel }}
                    </AppLink>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
