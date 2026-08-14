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
            <div class="relative mx-auto h-12 w-12">
                <div class="absolute inset-0 h-12 w-12 animate-spin rounded-full border-2 border-emerald-400/20"></div>
                <div class="absolute inset-0 h-12 w-12 animate-spin rounded-full border-2 border-transparent border-t-emerald-400" style="animation-duration: 1.5s"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="h-6 w-6 rounded-full bg-emerald-400/10 animate-pulse"></div>
                </div>
            </div>
            <div class="mt-6 text-sm font-medium text-white/60 animate-pulse">{{ state.translations?.admin?.layout?.loading }}</div>
            <div class="mt-2 text-xs text-white/40">{{ state.translations?.admin?.layout?.please_wait }}</div>
        </div>
    </div>
    <transition 
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <component v-if="!loading" :is="view" v-bind="pageProps" />
    </transition>
</template>
