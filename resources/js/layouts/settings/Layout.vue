<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/admin/appearance';
import { edit as editMail } from '@/routes/admin/mail';
import { edit as editProfile } from '@/routes/admin/profile';
import { edit as editSecurity } from '@/routes/admin/security';
import { edit as editStorage } from '@/routes/admin/storage';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: '个人资料',
        href: editProfile(),
    },
    {
        title: '安全',
        href: editSecurity(),
    },
    {
        title: '外观',
        href: editAppearance(),
    },
    {
        title: '存储',
        href: editStorage(),
    },
    {
        title: '邮箱',
        href: editMail(),
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="设置"
            description="管理账号与站点设置"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    aria-label="设置"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
