<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroupLabel,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { resolveMenuIcon } from '@/lib/menu-icons';
import type { SidebarMenuNode } from '@/types/menu';

const props = defineProps<{
    item: SidebarMenuNode;
}>();

const { isCurrentUrl } = useCurrentUrl();

const icon = computed(() => resolveMenuIcon(props.item.icon));

// 目录默认展开：任一后代节点为当前页时自动展开
const isChildActive = computed(() => containsActive(props.item.children ?? []));

function containsActive(nodes: SidebarMenuNode[]): boolean {
    return nodes.some(
        (node) =>
            (node.path !== null && isCurrentUrl(node.path)) ||
            containsActive(node.children ?? []),
    );
}
</script>

<template>
    <!-- 菜单：可跳转节点 -->
    <SidebarMenuItem v-if="item.type === 'menu'">
        <SidebarMenuButton
            as-child
            :is-active="item.path !== null && isCurrentUrl(item.path)"
            :tooltip="item.title"
        >
            <Link :href="item.path ?? '#'">
                <component :is="icon" />
                <span>{{ item.title }}</span>
            </Link>
        </SidebarMenuButton>
    </SidebarMenuItem>

    <!-- 标题：纯文字分组标签 -->
    <SidebarGroupLabel v-else-if="item.type === 'title'">
        {{ item.title }}
    </SidebarGroupLabel>

    <!-- 目录：可折叠节点，支持无限级嵌套 -->
    <Collapsible
        v-else
        :default-open="isChildActive"
        class="group/collapsible"
    >
        <SidebarMenuItem>
            <CollapsibleTrigger as-child>
                <SidebarMenuButton :tooltip="item.title">
                    <component :is="icon" />
                    <span>{{ item.title }}</span>
                    <ChevronRight
                        class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                    />
                </SidebarMenuButton>
            </CollapsibleTrigger>
            <CollapsibleContent>
                <SidebarMenuSub>
                    <NavMenuItem
                        v-for="child in item.children"
                        :key="child.id"
                        :item="child"
                    />
                </SidebarMenuSub>
            </CollapsibleContent>
        </SidebarMenuItem>
    </Collapsible>
</template>
