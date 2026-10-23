<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!-- 站点 Logo：蓝色小熊图标（矢量资源） -->
    <img
        src="/images/logo.svg?v=4"
        alt="站点 Logo"
        :class="className"
        v-bind="$attrs"
    >
</template>
