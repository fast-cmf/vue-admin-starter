<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { toUrl } from "@/lib/utils";
import type { NavItem } from "@/types";

const props = withDefaults(
    defineProps<{
        items: NavItem[];
        class?: string;
    }>(),
    {
        class: undefined,
    },
);

/**
 * 判断是否为站内链接：以 / 开头的地址使用 Inertia 路由跳转，否则视为外链新窗口打开
 */
function isInternal(href: NavItem["href"]): boolean {
    const url = toUrl(href);
    return typeof url === "string" && url.startsWith("/");
}

const containerClass = computed(
    () => `group-data-[collapsible=icon]:p-0 ${props.class || ""}`,
);
</script>

<template>
    <SidebarGroup :class="containerClass">
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <SidebarMenuButton
                        class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100"
                        as-child
                    >
                        <Link
                            v-if="isInternal(item.href)"
                            :href="toUrl(item.href)"
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                        <a
                            v-else
                            :href="toUrl(item.href)"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
