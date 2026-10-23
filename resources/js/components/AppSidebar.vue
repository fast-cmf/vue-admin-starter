<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { BookOpen, FolderGit2 } from "@lucide/vue";
import { computed } from "vue";
import AppLogo from "@/components/AppLogo.vue";
import NavFooter from "@/components/NavFooter.vue";
import NavMenuItem from "@/components/NavMenuItem.vue";
import NavUser from "@/components/NavUser.vue";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { dashboard, docs } from "@/routes/admin";
import type { NavItem } from "@/types";
import type { SidebarMenuNode } from "@/types/menu";

const page = usePage();

// 侧边栏菜单树：由「菜单管理」维护，后端按当前用户权限过滤后下发
const menus = computed<SidebarMenuNode[]>(() => page.props.menus ?? []);

const footerNavItems: NavItem[] = [
    {
        title: "代码仓库",
        href: "https://github.com/fast-cmf/vue-admin-starter",
        icon: FolderGit2,
    },
    {
        title: "文档",
        href: docs(),
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div class="px-2 py-0">
                <SidebarMenu>
                    <NavMenuItem
                        v-for="item in menus"
                        :key="item.id"
                        :item="item"
                    />
                </SidebarMenu>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
