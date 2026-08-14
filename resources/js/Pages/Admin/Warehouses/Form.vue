<script setup>
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppLink from '../../../admin/AppLink.vue';
import { useAdminForm } from '../../../admin/useAdminForm';
import { useAdminHead } from '../../../admin/useAdminHead';
import Alert from '../Partials/Alert.vue';

const props = defineProps({
    warehouse: { type: Object, default: null },
    translations: { type: Object, default: () => ({}) },
});

const t = computed(() => props.translations?.admin ?? {});

const isEdit = computed(() => props.warehouse !== null && props.warehouse !== undefined);

const form = useAdminForm({
    name: props.warehouse?.name ?? '',
    name_en: props.warehouse?.name_en ?? '',
    code: props.warehouse?.code ?? '',
    phone: props.warehouse?.phone ?? '',
    location: props.warehouse?.location ?? '',
    notes: props.warehouse?.notes ?? '',
    is_active: props.warehouse?.is_active ?? true,
});

const heading = computed(() =>
    isEdit.value ? t.value.warehouses?.edit_heading : t.value.warehouses?.create_heading
);
const subheading = computed(() =>
    isEdit.value
        ? (t.value.warehouses?.edit_sub ?? '').replace(':name', props.warehouse?.name ?? '')
        : t.value.warehouses?.create_sub
);

useAdminHead(isEdit.value ? t.value.warehouses?.edit_title : t.value.warehouses?.create_title);

const inputCls =
    'mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white placeholder-white/30 focus:border-emerald-400/40 focus:outline-none';

function submit() {
    if (isEdit.value) {
        form.put(`/admin/warehouses/${props.warehouse.id}`);
    } else {
        form.post('/admin/warehouses');
    }
}
</script>

<template>
    <AdminLayout :heading="heading" :subheading="subheading">
        <div class="admin-content">
            <Alert />

            <div class="mb-4">
                <AppLink href="/admin/warehouses" class="inline-flex items-center gap-1 text-xs text-emerald-200 hover:underline">
                    <span aria-hidden="true">&larr;</span>
                    {{ t.warehouses?.back_list }}
                </AppLink>
            </div>

            <form @submit.prevent="submit" class="max-w-2xl space-y-5">
                <div class="admin-card space-y-5 p-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-white/70">{{ t.warehouses?.name }}</label>
                            <input v-model="form.name" required :class="inputCls" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-300">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-white/70">{{ t.warehouses?.name_en }}</label>
                            <input v-model="form.name_en" :class="inputCls" dir="ltr" />
                            <p v-if="form.errors.name_en" class="mt-1 text-xs text-rose-300">{{ form.errors.name_en }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-white/70">{{ t.warehouses?.code }}</label>
                            <input v-model="form.code" :class="inputCls" dir="ltr" />
                            <p class="mt-1 text-[11px] text-white/40">{{ t.warehouses?.code_hint }}</p>
                            <p v-if="form.errors.code" class="mt-1 text-xs text-rose-300">{{ form.errors.code }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-white/70">{{ t.warehouses?.phone }}</label>
                            <input v-model="form.phone" :class="inputCls" dir="ltr" />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-300">{{ form.errors.phone }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.warehouses?.location }}</label>
                        <input v-model="form.location" :class="inputCls" />
                        <p v-if="form.errors.location" class="mt-1 text-xs text-rose-300">{{ form.errors.location }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-white/70">{{ t.warehouses?.notes }}</label>
                        <textarea v-model="form.notes" rows="3" :class="inputCls + ' resize-y'"></textarea>
                        <p v-if="form.errors.notes" class="mt-1 text-xs text-rose-300">{{ form.errors.notes }}</p>
                    </div>

                    <div>
                        <label class="inline-flex cursor-pointer items-center gap-2.5 text-sm text-white/80">
                            <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded accent-emerald-500" />
                            {{ t.warehouses?.is_active }}
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500 disabled:opacity-50"
                    >
                        {{ isEdit ? t.warehouses?.save : t.warehouses?.create_btn }}
                    </button>
                    <AppLink
                        href="/admin/warehouses"
                        class="rounded-xl border border-white/15 px-5 py-2.5 text-sm text-white/80 hover:bg-white/5"
                    >
                        {{ t.warehouses?.cancel }}
                    </AppLink>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
