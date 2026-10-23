<script setup lang="ts">
import { Link, Head } from "@inertiajs/vue3";
import { Info } from "@lucide/vue";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { docs } from "@/routes/admin";
import { index as loginLogIndex } from "@/routes/admin/login-logs";
import { index as operationLogIndex } from "@/routes/admin/operation-logs";
import { index as mediaIndex } from "@/routes/admin/media";
import { index as userIndex } from "@/routes/admin/users";
import { index as roleIndex } from "@/routes/admin/roles";
import { index as menuIndex } from "@/routes/admin/menus";
import { edit as profileEdit } from "@/routes/admin/profile";
import { edit as appearanceEdit } from "@/routes/admin/appearance";
import { edit as securityEdit } from "@/routes/admin/security";
import { edit as storageEdit } from "@/routes/admin/storage";
import { edit as mailEdit } from "@/routes/admin/mail";

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: "使用文档",
                href: docs(),
            },
        ],
    },
});

/** 文档章节内的小节链接 */
type DocLink = {
    id: string;
    title: string;
};

/** 文档章节：归属分组、锚点 id、标题、小节列表 */
type DocSection = DocLink & {
    group: string;
    children: DocLink[];
};

/** 左侧章节导航配置（分组展示） */
const navGroups: Array<{ title: string; items: DocLink[] }> = [
    {
        title: "快速开始",
        items: [
            { id: "introduction", title: "简介" },
            { id: "interface", title: "界面导览" },
            { id: "account", title: "登录与账号" },
        ],
    },
    {
        title: "核心功能",
        items: [
            { id: "dashboard", title: "仪表盘" },
            { id: "media", title: "媒体中心" },
            { id: "login-logs", title: "登录日志" },
            { id: "operation-logs", title: "操作日志" },
        ],
    },
    {
        title: "系统管理",
        items: [
            { id: "users", title: "用户管理" },
            { id: "roles", title: "角色管理" },
            { id: "permissions", title: "权限体系" },
        ],
    },
    {
        title: "系统设置",
        items: [
            { id: "profile", title: "个人资料" },
            { id: "appearance", title: "外观" },
            { id: "security", title: "安全" },
            { id: "storage", title: "存储" },
            { id: "mail", title: "邮箱" },
        ],
    },
    {
        title: "附录",
        items: [{ id: "faq", title: "常见问题" }],
    },
];

/** 全部章节的平铺配置，供右侧「本页目录」按当前章节展示小节 */
const sections: DocSection[] = [
    {
        id: "introduction",
        title: "简介",
        group: "快速开始",
        children: [],
    },
    {
        id: "interface",
        title: "界面导览",
        group: "快速开始",
        children: [
            { id: "interface-sidebar", title: "左侧边栏" },
            { id: "interface-topbar", title: "顶部与内容区" },
        ],
    },
    {
        id: "account",
        title: "登录与账号",
        group: "快速开始",
        children: [
            { id: "account-login", title: "登录后台" },
            { id: "account-forgot", title: "忘记密码" },
            { id: "account-logout", title: "退出登录" },
        ],
    },
    {
        id: "dashboard",
        title: "仪表盘",
        group: "核心功能",
        children: [],
    },
    {
        id: "media",
        title: "媒体中心",
        group: "核心功能",
        children: [
            { id: "media-upload", title: "上传文件" },
            { id: "media-filter", title: "筛选与搜索" },
            { id: "media-manage", title: "编辑与删除" },
        ],
    },
    {
        id: "login-logs",
        title: "登录日志",
        group: "核心功能",
        children: [
            { id: "login-logs-fields", title: "记录内容" },
            { id: "login-logs-actions", title: "日常操作" },
        ],
    },
    {
        id: "operation-logs",
        title: "操作日志",
        group: "核心功能",
        children: [
            { id: "operation-logs-fields", title: "记录内容" },
            { id: "operation-logs-actions", title: "日常操作" },
        ],
    },
    {
        id: "users",
        title: "用户管理",
        group: "系统管理",
        children: [
            { id: "users-create", title: "新增用户" },
            { id: "users-edit", title: "编辑用户" },
            { id: "users-block", title: "拉黑与解封" },
            { id: "users-assign-roles", title: "分配角色" },
        ],
    },
    {
        id: "roles",
        title: "角色管理",
        group: "系统管理",
        children: [
            { id: "roles-name-code", title: "角色名称与角色标识" },
            { id: "roles-create", title: "新建与编辑角色" },
            { id: "roles-matrix", title: "权限矩阵" },
            { id: "roles-builtin", title: "内置角色保护" },
        ],
    },
    {
        id: "permissions",
        title: "权限体系",
        group: "系统管理",
        children: [
            { id: "permissions-concept", title: "权限标识" },
            { id: "permissions-sidebar", title: "侧边栏菜单过滤" },
            { id: "permissions-buttons", title: "页面按钮显隐" },
            { id: "permissions-recipe", title: "典型配置流程" },
            { id: "permissions-sync", title: "权限同步" },
        ],
    },
    {
        id: "profile",
        title: "个人资料",
        group: "系统设置",
        children: [],
    },
    {
        id: "appearance",
        title: "外观",
        group: "系统设置",
        children: [],
    },
    {
        id: "security",
        title: "安全",
        group: "系统设置",
        children: [
            { id: "security-password", title: "修改密码" },
            { id: "security-2fa", title: "两步验证" },
            { id: "security-passkey", title: "通行密钥" },
        ],
    },
    {
        id: "storage",
        title: "存储",
        group: "系统设置",
        children: [
            { id: "storage-switch", title: "切换存储方式" },
            { id: "storage-notes", title: "注意事项" },
        ],
    },
    {
        id: "mail",
        title: "邮箱",
        group: "系统设置",
        children: [
            { id: "mail-smtp", title: "启用 SMTP" },
            { id: "mail-test", title: "发送测试邮件" },
        ],
    },
    {
        id: "faq",
        title: "常见问题",
        group: "附录",
        children: [],
    },
];

// 当前阅读到的章节 id，由滚动监听更新
const activeId = ref("introduction");

/** 右侧「本页目录」展示的小节列表：当前章节的小节；无小节时展示章节本身 */
const tocItems = computed<DocLink[]>(() => {
    const section = sections.find((item) => item.id === activeId.value);
    if (!section) {
        return [];
    }
    return section.children.length > 0
        ? section.children
        : [{ id: section.id, title: section.title }];
});

/**
 * 平滑滚动到指定章节锚点，并同步地址栏锚点
 */
function scrollTo(id: string): void {
    document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
    history.replaceState(null, "", `#${id}`);
}

/**
 * 根据滚动位置更新当前章节：取视口内最后一个越过顶部偏移的章节
 */
function handleScroll(): void {
    // 页面滚到底部时，最后一节（常因内容不足无法到达视口顶部）强制激活
    const doc = document.documentElement;
    if (window.innerHeight + window.scrollY >= doc.scrollHeight - 4) {
        activeId.value = sections[sections.length - 1]?.id ?? activeId.value;
        return;
    }
    let current = sections[0]?.id ?? '';
    for (const section of sections) {
        const el = document.getElementById(section.id);
        if (el && el.getBoundingClientRect().top <= 120) {
            current = section.id;
        }
    }
    activeId.value = current;
}

onMounted(() => {
    window.addEventListener("scroll", handleScroll, { passive: true });
    handleScroll();
});

onBeforeUnmount(() => {
    window.removeEventListener("scroll", handleScroll);
});
</script>

<template>
    <Head title="使用文档" />

    <h1 class="sr-only">使用文档</h1>

    <div class="flex flex-col gap-6 p-4 lg:p-6">
        <div
            class="lg:grid lg:grid-cols-[210px_minmax(0,1fr)] lg:gap-10 xl:grid-cols-[210px_minmax(0,1fr)_210px]"
        >
            <!-- 左侧章节导航 -->
            <aside
                class="hidden lg:block lg:sticky lg:top-20 lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto"
            >
                <nav class="flex flex-col gap-6 pb-8 text-sm">
                    <div v-for="group in navGroups" :key="group.title">
                        <p
                            class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground"
                        >
                            {{ group.title }}
                        </p>
                        <ul
                            class="flex flex-col gap-0.5 border-l border-border"
                        >
                            <li v-for="item in group.items" :key="item.id">
                                <button
                                    type="button"
                                    :data-test="`docs-nav-${item.id}`"
                                    class="w-full border-l-2 border-transparent py-1 pl-3 text-left text-muted-foreground transition-colors hover:text-foreground"
                                    :class="
                                        activeId === item.id
                                            ? '-ml-px border-primary font-medium text-primary'
                                            : ''
                                    "
                                    @click="scrollTo(item.id)"
                                >
                                    {{ item.title }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </nav>
            </aside>

            <!-- 中间正文 -->
            <main class="flex flex-col gap-10 pb-16 text-sm leading-7">
                <section id="introduction" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        简介
                    </h2>
                    <p class="text-muted-foreground">
                        欢迎使用本站点后台。后台是网站的运营管理中枢，登录后可以完成内容管理与系统运维两大类工作：
                    </p>
                    <ul
                        class="mt-3 list-disc space-y-1 pl-6 text-muted-foreground"
                    >
                        <li>内容管理：仪表盘、媒体中心；</li>
                        <li>审计追踪：登录日志、操作日志；</li>
                        <li>系统配置：个人资料、外观、安全、存储、邮箱。</li>
                    </ul>
                    <div
                        class="mt-4 flex items-start gap-2 rounded-lg border border-primary/20 bg-primary/5 p-4"
                    >
                        <Info class="mt-1 size-4 shrink-0 text-primary" />
                        <p class="text-muted-foreground">
                            后台入口为
                            <code class="rounded bg-muted px-1.5 py-0.5 text-xs"
                                >/admin</code
                            >
                            ，未登录访问任意后台页面都会跳转到登录页，登录成功后默认进入仪表盘。
                        </p>
                    </div>
                </section>

                <section id="interface" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        界面导览
                    </h2>
                    <p class="text-muted-foreground">
                        后台采用经典的侧边栏布局，主要分为三个区域。
                    </p>
                    <h3
                        id="interface-sidebar"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        左侧边栏
                    </h3>
                    <p class="text-muted-foreground">
                        顶部是站点
                        Logo，点击可回到仪表盘；中部为功能导航（仪表盘、媒体中心、登录日志、操作日志）；底部提供代码仓库与本文档的入口，以及当前登录用户的头像菜单。侧边栏支持折叠为纯图标模式，点击顶部折叠按钮即可切换。
                    </p>
                    <h3
                        id="interface-topbar"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        顶部与内容区
                    </h3>
                    <p class="text-muted-foreground">
                        内容区顶部展示当前页面的面包屑路径，方便确认所在位置；其余区域为各功能页面的主体。列表页统一提供搜索、筛选、分页与批量操作，交互习惯保持一致，上手一个页面即可举一反三。
                    </p>
                </section>

                <section id="account" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        登录与账号
                    </h2>
                    <h3
                        id="account-login"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        登录后台
                    </h3>
                    <p class="text-muted-foreground">
                        浏览器访问
                        <code class="rounded bg-muted px-1.5 py-0.5 text-xs"
                            >/admin/login</code
                        >
                        ，输入邮箱与密码即可登录。如果账号开启了两步验证，还需要输入验证器
                        App 中的动态验证码。
                    </p>
                    <h3
                        id="account-forgot"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        忘记密码
                    </h3>
                    <p class="text-muted-foreground">
                        在登录页点击「忘记密码」，输入注册邮箱后系统会发送一封包含重置链接的邮件，按邮件指引设置新密码即可。
                    </p>
                    <h3
                        id="account-logout"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        退出登录
                    </h3>
                    <p class="text-muted-foreground">
                        点击左下角的用户头像菜单，选择「退出登录」即可安全退出当前会话。公共电脑上使用完毕后请务必退出。
                    </p>
                </section>

                <section id="dashboard" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        仪表盘
                    </h2>
                    <p class="text-muted-foreground">
                        仪表盘是登录后的默认页面，用于汇总展示站点的核心数据概览。当前版本为预留区域，后续将逐步加入媒体数量、日志趋势等统计卡片，帮助你快速掌握站点运行状况。
                    </p>
                </section>

                <section id="media" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        媒体中心
                    </h2>
                    <p class="text-muted-foreground">
                        <Link
                            :href="mediaIndex()"
                            class="font-medium text-primary hover:underline"
                        >
                            媒体中心
                        </Link>
                        统一管理站点上传的图片、视频、音频与文档，所有文件按类型归类展示。
                    </p>
                    <h3
                        id="media-upload"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        上传文件
                    </h3>
                    <p class="text-muted-foreground">
                        点击页面右上角的「上传」按钮选择文件，支持一次选择多个文件批量上传。支持图片、视频、音频与常见文档类型，单文件大小不超过
                        50MB。
                    </p>
                    <h3
                        id="media-filter"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        筛选与搜索
                    </h3>
                    <p class="text-muted-foreground">
                        左侧提供类型筛选（全部 / 图片 / 视频 / 音频 /
                        文件），并显示各类型的数量统计；顶部搜索框可按标题或文件夹模糊搜索。筛选与搜索结果分页展示。
                    </p>
                    <h3
                        id="media-manage"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        编辑与删除
                    </h3>
                    <p class="text-muted-foreground">
                        点击卡片可预览文件详情；通过「编辑」可修改标题与所属文件夹；图片支持在线裁剪；删除操作需二次确认，删除后不可恢复，请谨慎操作。
                    </p>
                </section>

                <section id="login-logs" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        登录日志
                    </h2>
                    <p class="text-muted-foreground">
                        <Link
                            :href="loginLogIndex()"
                            class="font-medium text-primary hover:underline"
                        >
                            登录日志
                        </Link>
                        记录每一个账号的登录行为，是安全审计的第一手资料。
                    </p>
                    <h3
                        id="login-logs-fields"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        记录内容
                    </h3>
                    <p class="text-muted-foreground">
                        每条记录包含登录账号、状态（成功 / 失败）、IP
                        地址、浏览器与操作系统、登录时间等信息；登录失败时（如密码错误）同样会被记录，并附带失败原因。
                    </p>
                    <h3
                        id="login-logs-actions"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        日常操作
                    </h3>
                    <p class="text-muted-foreground">
                        支持按账号搜索、按状态筛选、查看单条详情、导出 CSV
                        存档，以及批量删除与一键清空。删除与清空操作不可恢复，建议先导出再清理。
                    </p>
                </section>

                <section id="operation-logs" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        操作日志
                    </h2>
                    <p class="text-muted-foreground">
                        <Link
                            :href="operationLogIndex()"
                            class="font-medium text-primary hover:underline"
                        >
                            操作日志
                        </Link>
                        自动记录后台发生的所有写操作（新增、修改、删除），普通的浏览与查询不会产生记录。
                    </p>
                    <h3
                        id="operation-logs-fields"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        记录内容
                    </h3>
                    <p class="text-muted-foreground">
                        每条记录包含操作人、所属模块、操作摘要、类型、执行结果、请求地址、IP
                        与耗时；操作失败时还会记录具体的错误信息，方便回溯问题。
                    </p>
                    <h3
                        id="operation-logs-actions"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        日常操作
                    </h3>
                    <p class="text-muted-foreground">
                        与登录日志一致，支持搜索、详情查看、CSV
                        导出与批量删除。可按模块与操作人快速定位某次变更的来龙去脉。
                    </p>
                </section>

                <section id="users" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        用户管理
                    </h2>
                    <p class="text-muted-foreground">
                        <Link
                            :href="userIndex()"
                            class="font-medium text-primary hover:underline"
                        >
                            用户管理
                        </Link>
                        用于维护后台账号：新增同事的登录账号、修改资料、重置密码、拉黑违规账号，以及为用户分配角色。
                    </p>
                    <h3
                        id="users-create"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        新增用户
                    </h3>
                    <p class="text-muted-foreground">
                        点击右上角「新增用户」，填写用户名称、邮箱地址与登录密码即可创建账号。新账号的邮箱默认未验证，创建后可直接用邮箱密码登录，再由管理员分配角色获得对应权限。
                    </p>
                    <h3
                        id="users-edit"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        编辑用户
                    </h3>
                    <p class="text-muted-foreground">
                        在列表操作列点击「编辑」可修改用户名称与邮箱地址。「重置密码」留空表示不修改，填写新密码则直接覆盖原密码，适合在用户忘记密码时由管理员代为重置。
                    </p>
                    <h3
                        id="users-block"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        拉黑与解封
                    </h3>
                    <p class="text-muted-foreground">
                        点击「拉黑」并确认后，该用户会被立即踢出登录，且无法再次登录（即使密码正确也会被拒绝）。拉黑随时可逆——点击「解除拉黑」后账号即可重新登录。当前登录账号不能拉黑自己，超级管理员不可被拉黑。
                    </p>
                    <h3
                        id="users-assign-roles"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        分配角色
                    </h3>
                    <p class="text-muted-foreground">
                        点击「分配角色」在弹窗中勾选该用户应拥有的角色，保存后立即生效。系统始终要求至少保留一名超级管理员，因此最后一名超管的超管角色无法被移除。
                    </p>
                </section>

                <section id="roles" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        角色管理
                    </h2>
                    <p class="text-muted-foreground">
                        <Link
                            :href="roleIndex()"
                            class="font-medium text-primary hover:underline"
                        >
                            角色管理
                        </Link>
                        是权限分配的核心：先在角色上勾选一组权限标识，再把角色分配给用户，用户即获得角色内的全部权限。
                    </p>
                    <h3
                        id="roles-name-code"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        角色名称与角色标识
                    </h3>
                    <p class="text-muted-foreground">
                        角色名称是页面上显示的中文名（如「运营编辑」），可随时修改；角色标识是系统内部使用的代码（如
                        editor），创建后不可修改。日常使用只需关注角色名称。
                    </p>
                    <h3
                        id="roles-create"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        新建与编辑角色
                    </h3>
                    <p class="text-muted-foreground">
                        点击「新建角色」填写角色名称与角色标识（仅限字母、数字、中划线、下划线）创建；「编辑」只能修改角色名称，角色标识与创建后不可变。
                    </p>
                    <h3
                        id="roles-matrix"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        权限矩阵
                    </h3>
                    <p class="text-muted-foreground">
                        点击「权限配置」打开权限矩阵，权限按分组展示（系统管理、系统日志等）。每组标题行带全选框：未选或半选时点击全选本组，已全选时点击取消本组。勾选完成后保存即可同步该角色的全部权限。
                    </p>
                    <h3
                        id="roles-builtin"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        内置角色保护
                    </h3>
                    <p class="text-muted-foreground">
                        「超级管理员」是系统内置角色，拥有全部权限且不可编辑、不可删除。超级管理员通过全局放行规则绕过所有权限检查，请谨慎分配该角色。
                    </p>
                </section>

                <section id="permissions" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        权限体系
                    </h2>
                    <p class="text-muted-foreground">
                        后台采用「角色 + 权限标识」模型：权限标识定义系统里有哪些权限，角色是一组权限标识的集合，用户通过角色获得权限。同一个权限标识同时控制三件事：能否访问对应页面、能否在侧边栏看到菜单、能否看到页面上的操作按钮。
                    </p>
                    <h3
                        id="permissions-concept"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        权限标识
                    </h3>
                    <p class="text-muted-foreground">
                        权限标识形如「模块.动作」（如
                        media.upload
                        表示上传媒体），每个标识都有唯一的中文名称（如「上传媒体」）。标识由系统统一登记，新建功能时会自动出现，无需手工维护。
                    </p>
                    <h3
                        id="permissions-sidebar"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        侧边栏菜单过滤
                    </h3>
                    <p class="text-muted-foreground">
                        每条菜单都绑定了一个权限标识：用户拥有该权限时菜单才显示，没有则整条隐藏；目录下的菜单全部被隐藏时，目录本身也会一并收起。不绑定权限标识的菜单对所有用户可见。
                    </p>
                    <h3
                        id="permissions-buttons"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        页面按钮显隐
                    </h3>
                    <p class="text-muted-foreground">
                        页面内的操作按钮（如「新增用户」「导出」「拉黑」）同样按权限标识显隐：没有对应权限时按钮直接不渲染。即使有人绕过界面直接发起请求，后端仍会拦截并返回无权限提示——前端隐藏与后端拦截互为双保险。
                    </p>
                    <h3
                        id="permissions-recipe"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        典型配置流程
                    </h3>
                    <p class="text-muted-foreground">
                        给新同事开通后台账号的推荐流程：在
                        <Link
                            :href="userIndex()"
                            class="font-medium text-primary hover:underline"
                            >用户管理</Link
                        >
                        新增用户 → 在
                        <Link
                            :href="roleIndex()"
                            class="font-medium text-primary hover:underline"
                            >角色管理</Link
                        >
                        新建角色并勾选所需权限（或复用已有角色）→ 回到用户管理为该用户「分配角色」。如需调整侧边栏菜单本身的显示结构或可见性，可在
                        <Link
                            :href="menuIndex()"
                            class="font-medium text-primary hover:underline"
                            >菜单管理</Link
                        >
                        中维护目录、菜单与按钮三类节点。
                    </p>
                    <h3
                        id="permissions-sync"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        权限同步
                    </h3>
                    <p class="text-muted-foreground">
                        权限标识统一登记在项目的
                        <code class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs">config/permissions.php</code>
                        配置文件中。开发人员新增或修改权限标识后，需要在项目根目录执行一次同步命令，把变更写入数据库：
                    </p>
                    <pre class="mt-3 overflow-x-auto rounded-lg border bg-muted/50 p-3 font-mono text-xs text-foreground">php artisan permission:sync</pre>
                    <p class="mt-3 text-muted-foreground">
                        该命令只会新增和更新权限标识，不会删除已有权限，也不影响各角色已勾选的权限；同时会自动检查所有路由引用的权限标识是否均已登记。执行完成后，新权限即可在
                        <Link
                            :href="roleIndex()"
                            class="font-medium text-primary hover:underline"
                            >角色管理</Link
                        >
                        的权限矩阵中勾选使用。
                    </p>
                </section>

                <section id="profile" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        个人资料
                    </h2>
                    <p class="text-muted-foreground">
                        在
                        <Link
                            :href="profileEdit()"
                            class="font-medium text-primary hover:underline"
                            >设置 → 个人资料</Link
                        >
                        中可以维护账号信息：上传或更换头像、修改名称与邮箱地址。修改邮箱后需要通过新邮箱的验证，验证完成前系统会持续提醒。
                    </p>
                </section>

                <section id="appearance" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        外观
                    </h2>
                    <p class="text-muted-foreground">
                        在
                        <Link
                            :href="appearanceEdit()"
                            class="font-medium text-primary hover:underline"
                            >设置 → 外观</Link
                        >
                        中可以切换界面的显示模式：浅色、深色或跟随系统。选择后立即生效并会被记住，下次访问保持一致。
                    </p>
                </section>

                <section id="security" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        安全
                    </h2>
                    <p class="text-muted-foreground">
                        <Link
                            :href="securityEdit()"
                            class="font-medium text-primary hover:underline"
                            >设置 → 安全</Link
                        >
                        集中管理账号的认证方式，建议至少开启两步验证。
                    </p>
                    <h3
                        id="security-password"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        修改密码
                    </h3>
                    <p class="text-muted-foreground">
                        修改密码需要先输入当前密码进行确认，新密码需满足强度要求（长度与复杂度）。
                    </p>
                    <h3
                        id="security-2fa"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        两步验证
                    </h3>
                    <p class="text-muted-foreground">
                        开启后，登录时除密码外还需输入验证器 App（如 Google
                        Authenticator）生成的动态验证码。开启时请按页面提示妥善保存恢复代码，手机丢失时可凭恢复代码进入账号。
                    </p>
                    <h3
                        id="security-passkey"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        通行密钥
                    </h3>
                    <p class="text-muted-foreground">
                        可以为账号绑定 Passkey
                        通行密钥（指纹、面容或硬件安全密钥），之后无需输入密码即可登录，安全性与便捷性兼得。
                    </p>
                </section>

                <section id="storage" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        存储
                    </h2>
                    <p class="text-muted-foreground">
                        在
                        <Link
                            :href="storageEdit()"
                            class="font-medium text-primary hover:underline"
                            >设置 → 存储</Link
                        >
                        中配置媒体文件的存储方式，默认使用服务器本地磁盘。
                    </p>
                    <h3
                        id="storage-switch"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        切换存储方式
                    </h3>
                    <p class="text-muted-foreground">
                        选择「OSS 对象存储（S3 兼容）」后，需要填写
                        Bucket、Endpoint、Region、AccessKey ID 与
                        Secret，并可按需自定义文件的访问域名。保存后新上传的文件将直接写入对象存储。
                    </p>
                    <h3
                        id="storage-notes"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        注意事项
                    </h3>
                    <p class="text-muted-foreground">
                        已上传的文件记录了各自的所属磁盘，切换存储方式后历史文件的访问链接依然有效；密钥保存后不会回显，仅提示「已设置」。配置异常导致文件不可访问时，可先切回本地存储排查。
                    </p>
                </section>

                <section id="mail" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        邮箱
                    </h2>
                    <p class="text-muted-foreground">
                        在
                        <Link
                            :href="mailEdit()"
                            class="font-medium text-primary hover:underline"
                            >设置 → 邮箱</Link
                        >
                        中配置发信能力，密码重置等系统邮件依赖此配置。
                    </p>
                    <h3
                        id="mail-smtp"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        启用 SMTP
                    </h3>
                    <p class="text-muted-foreground">
                        默认使用系统内置的邮件配置；启用自定义 SMTP 后，需填写
                        SMTP 服务器地址、端口、账号、密码与加密方式（无 / SSL /
                        TLS），并配置发件人地址与名称。
                    </p>
                    <h3
                        id="mail-test"
                        class="mb-2 mt-6 scroll-mt-20 text-base font-semibold"
                    >
                        发送测试邮件
                    </h3>
                    <p class="text-muted-foreground">
                        保存配置后，在页面底部填写接收邮箱并点击「发送测试邮件」，能收到邮件即代表配置成功。测试发送有频率限制，请勿连续点击。
                    </p>
                </section>

                <section id="faq" class="scroll-mt-20">
                    <h2
                        class="mb-4 scroll-mt-20 text-2xl font-semibold tracking-tight"
                    >
                        常见问题
                    </h2>
                    <dl class="flex flex-col gap-5 text-muted-foreground">
                        <div>
                            <dt class="font-medium text-foreground">
                                登录后总是跳回登录页？
                            </dt>
                            <dd>
                                请检查浏览器是否禁用了
                                Cookie，或是否使用了会清理会话的隐私模式。
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-foreground">
                                上传文件失败？
                            </dt>
                            <dd>
                                确认文件类型为图片 / 视频 / 音频 /
                                常见文档，且大小不超过
                                50MB；若已切换对象存储，配置异常时可先切回本地存储验证。
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-foreground">
                                收不到测试邮件？
                            </dt>
                            <dd>
                                核对 SMTP
                                主机、端口与加密方式是否匹配；部分云服务器默认封禁
                                25 端口，建议使用 465（SSL）或 587（TLS）。
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-foreground">
                                误删了日志怎么办？
                            </dt>
                            <dd>
                                日志删除与清空均不可恢复，清理前建议先导出 CSV
                                存档。
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-foreground">
                                如何修改站点品牌配色？
                            </dt>
                            <dd>
                                外观设置仅切换明暗显示模式；品牌主色由系统统一定义在
                                <code
                                    class="rounded bg-muted px-1.5 py-0.5 text-xs"
                                    >resources/css/app.css</code
                                >
                                中，如需调整请联系开发人员。
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-foreground">
                                侧边栏看不到某个菜单？
                            </dt>
                            <dd>
                                菜单按权限标识过滤：当前账号没有该菜单绑定的权限时整条隐藏，目录下的菜单全部隐藏时目录也会收起。请联系管理员在角色管理中为你补充对应权限。
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-foreground">
                                页面上的操作按钮不见了？
                            </dt>
                            <dd>
                                操作按钮（如新增、导出、拉黑）与菜单一样按权限显隐。没有对应权限时按钮不显示，且直接发起请求也会被后端拦截。请联系管理员分配包含相应权限的角色。
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-foreground">
                                账号被拉黑了怎么办？
                            </dt>
                            <dd>
                                被拉黑的账号会被立即退出登录且无法再次登录。请联系管理员在用户管理中「解除拉黑」，解封后即可正常登录，原密码不变。
                            </dd>
                        </div>
                    </dl>
                </section>

                <p
                    class="border-t border-border pt-6 text-xs text-muted-foreground"
                >
                    本文档随后台功能持续更新，如发现描述与实际界面不符，请联系管理员。
                </p>
            </main>

            <!-- 右侧本页目录 -->
            <aside
                class="hidden xl:block xl:sticky xl:top-20 xl:max-h-[calc(100vh-6rem)] xl:overflow-y-auto"
            >
                <p
                    class="mb-3 text-xs font-semibold tracking-wide text-muted-foreground"
                >
                    本页目录
                </p>
                <ul class="flex flex-col gap-2 border-l border-border text-sm">
                    <li v-for="item in tocItems" :key="item.id">
                        <button
                            type="button"
                            class="w-full border-l-2 border-transparent py-0.5 pl-3 text-left text-muted-foreground transition-colors hover:text-foreground"
                            :class="
                                activeId === item.id
                                    ? '-ml-px border-primary font-medium text-primary'
                                    : ''
                            "
                            @click="scrollTo(item.id)"
                        >
                            {{ item.title }}
                        </button>
                    </li>
                </ul>
            </aside>
        </div>
    </div>
</template>
