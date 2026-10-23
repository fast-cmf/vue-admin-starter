<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/admin/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: '外观设置',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="外观设置" />

    <h1 class="sr-only">外观设置</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="外观设置"
            description="更新账号的外观设置"
        />
        <AppearanceTabs />
    </div>
</template>
