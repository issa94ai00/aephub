<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { isSpaPath } from './adminRouter';

const props = defineProps({
    href: { type: String, required: true },
});

const isSpa = computed(() => isSpaPath(props.href));
</script>

<template>
    <RouterLink v-if="isSpa" :to="href" v-bind="$attrs">
        <slot />
    </RouterLink>
    <a v-else :href="href" v-bind="$attrs">
        <slot />
    </a>
</template>
