<script setup>
import { computed, nextTick, onMounted, ref, shallowRef, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { refreshShell } from './dom';
import { applyPayload, consumePending, pathname, state } from './state';

const route = useRoute();

const loading = ref(true);
const pageData = shallowRef(null);

const view = computed(() => route.matched?.[0]?.components?.default ?? null);

const pageProps = computed(() => {
    const d = pageData.value ?? {};
    const { flash, errors, adminChrome, ...rest } = d;
    return rest;
});

async function load() {
    loading.value = true;

    const pending = consumePending(route.path);
    if (pending) {
        pageData.value = pending.props ?? {};
        applyPayload(pending);
    } else if (!state.bootConsumed && state.boot && pathname(state.boot.url) === route.path) {
        state.bootConsumed = true;
        pageData.value = state.boot.props ?? {};
        applyPayload(state.boot);
    } else {
        try {
            const res = await axios.get(route.fullPath);
            const payload = res.data ?? {};
            pageData.value = payload.props ?? {};
            applyPayload(payload);
        } catch (e) {
            if (e?.response?.status === 401) {
                window.location.href = '/admin/login';
                return;
            }
            if (e?.response?.status === 403) {
                window.location.href = '/admin';
                return;
            }
            pageData.value = {};
        }
    }

    loading.value = false;
    await nextTick();
    refreshShell();
}

onMounted(() => {
    load();
});

watch(
    () => [route.fullPath, state.refreshTick],
    () => {
        load();
    }
);
</script>

<template>
    <div v-if="loading" class="flex min-h-screen items-center justify-center p-8">
        <div class="text-center">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-2 border-emerald-400/30 border-t-emerald-400"></div>
            <div class="mt-4 text-sm text-white/60">{{ state.translations?.admin?.layout?.loading }}</div>
        </div>
    </div>
    <component v-else :is="view" v-bind="pageProps" />
</template>
