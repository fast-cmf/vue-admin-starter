<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    BookOpen,
    Code2,
    FileImage,
    Fingerprint,
    KeyRound,
    LayoutDashboard,
    LifeBuoy,
    Lightbulb,
    ListChecks,
    Lock,
    MessagesSquare,
    Settings,
    ShieldCheck,
    Zap,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import AppLogo from '@/components/AppLogo.vue';
import { dashboard } from '@/routes/admin';
import { home, login, register } from '@/routes';
import type { Auth } from '@/types';

const { auth } = defineProps<{ auth: Auth }>();

/** 是否已登录：已登录时导航直接展示「进入后台」 */
const isAuthenticated = computed(() => auth.user !== null);

/** 各入口链接地址（Wayfinder 路由取 URL 字符串） */
const urls = {
    home: home.url(),
    dashboard: dashboard.url(),
    login: login.url(),
    register: register.url(),
};

/** Hero 区右侧插画（SDXL 风格提示词，宝蓝主色系） */
const heroIllustration = computed(
    () =>
        'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=' +
        encodeURIComponent(
            'cute blue bear mascot character wearing a headset working on a laptop, next to floating admin dashboard UI cards and charts, flat vector illustration, royal blue and light blue color palette, soft pastel background, clean minimal modern web illustration, no text',
        ) +
        '&image_size=landscape_4_3',
);

/**
 * 功能分组数据：分组标题 + 简介 + 卡片列表
 */
const featureGroups = [
    {
        title: '核心功能',
        description: '覆盖日常内容与系统管理的常用能力，开箱即用。',
        items: [
            {
                icon: FileImage,
                title: '媒体中心',
                description: '集中管理图片素材，支持上传、裁剪与云端存储自由切换。',
            },
            {
                icon: Settings,
                title: '系统设置',
                description: '存储、邮箱等运行参数后台可视化配置，改完即生效。',
            },
            {
                icon: ListChecks,
                title: '日志审计',
                description: '登录与操作全量留痕，谁在什么时间做了什么一目了然。',
            },
        ],
    },
    {
        title: '安全与账号',
        description: '多层防护守护每一个后台账号，让登录与权限更安心。',
        items: [
            {
                icon: ShieldCheck,
                title: '两步验证',
                description: '基于 TOTP 的两步验证，防止密码泄露导致的账号风险。',
            },
            {
                icon: Fingerprint,
                title: 'Passkey 免密登录',
                description: '支持指纹 / 面容识别的 WebAuthn 登录，告别弱密码。',
            },
            {
                icon: KeyRound,
                title: '密码策略',
                description: '内置强密码校验与安全找回流程，降低账号被盗风险。',
            },
        ],
    },
    {
        title: '开发者友好',
        description: '现代技术栈与自动化工具链，二次开发顺滑高效。',
        items: [
            {
                icon: Code2,
                title: '类型安全全栈',
                description: 'Laravel + Vue 全栈类型提示，路由函数自动生成。',
            },
            {
                icon: BookOpen,
                title: 'API 文档',
                description: 'API 自动生成 OpenAPI 文档，接口约定实时可见。',
            },
            {
                icon: Zap,
                title: '质量内建',
                description: '测试、静态分析与格式化全覆盖，重构不心虚。',
            },
        ],
    },
];

/**
 * 页脚导航列数据
 */
const footerColumns = [
    {
        title: '产品',
        links: [
            { label: '功能特性', href: '#features' },
            { label: '进入后台', href: urls.dashboard },
        ],
    },
    {
        title: '资源',
        links: [
            { label: 'API 文档', href: '/docs/api' },
            { label: 'OpenAPI 规范', href: '/docs/api.json' },
        ],
    },
    {
        title: '支持',
        links: [
            { label: '问题反馈', href: 'mailto:support@example.com' },
            { label: '联系我们', href: 'mailto:support@example.com' },
        ],
    },
];
</script>

<template>
    <Head title="欢迎" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- 顶部导航 -->
        <header class="sticky top-0 z-10 border-b bg-background/80 backdrop-blur">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4 lg:px-6">
                <Link
                    :href="urls.home"
                    class="flex items-center font-semibold"
                >
                    <AppLogo />
                </Link>

                <nav class="hidden items-center gap-6 text-sm text-muted-foreground md:flex">
                    <a
                        href="#features"
                        class="transition-colors hover:text-foreground"
                    >功能特性</a>
                    <a
                        href="#feedback"
                        class="transition-colors hover:text-foreground"
                    >反馈支持</a>
                    <a
                        href="/docs/api"
                        class="transition-colors hover:text-foreground"
                    >API 文档</a>
                </nav>

                <div class="flex items-center gap-2">
                    <template v-if="isAuthenticated">
                        <Button
                            as-child
                            data-test="goto-dashboard-button"
                        >
                            <Link :href="urls.dashboard">
                                <LayoutDashboard class="size-4" />
                                进入后台
                            </Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button
                            as-child
                            variant="ghost"
                            data-test="goto-login-button"
                        >
                            <Link :href="urls.login">登录</Link>
                        </Button>
                        <Button
                            as-child
                            data-test="goto-register-button"
                        >
                            <Link :href="urls.register">免费注册</Link>
                        </Button>
                    </template>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <!-- Hero 区：大标题 + 描述 + 插画 -->
            <section class="mx-auto grid w-full max-w-6xl items-center gap-10 px-4 py-16 lg:grid-cols-2 lg:px-6 lg:py-24">
                <div class="flex flex-col items-start gap-5">
                    <h1 class="text-4xl font-bold tracking-tight lg:text-5xl">
                        轻松管理你的
                        <span class="text-primary">内容与站点</span>
                    </h1>
                    <p class="max-w-xl text-base leading-relaxed text-muted-foreground lg:text-lg">
                        从媒体素材到系统配置，从登录安全到操作审计，
                        一个后台全部搞定。界面简洁、上手零门槛，
                        把时间留给真正重要的事。
                    </p>
                    <div class="flex flex-wrap items-center gap-3">
                        <Button
                            as-child
                            size="lg"
                            data-test="hero-primary-button"
                        >
                            <Link :href="isAuthenticated ? urls.dashboard : urls.register">
                                {{ isAuthenticated ? '进入后台' : '免费开始使用' }}
                            </Link>
                        </Button>
                        <Button
                            as-child
                            size="lg"
                            variant="outline"
                            data-test="hero-secondary-button"
                        >
                            <Link :href="urls.login">
                                {{ isAuthenticated ? '切换账号' : '登录后台' }}
                            </Link>
                        </Button>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border bg-primary/5 shadow-xs">
                    <img
                        :src="heroIllustration"
                        alt="后台管理插画"
                        class="aspect-[4/3] w-full object-cover"
                        loading="eager"
                    >
                </div>
            </section>

            <!-- 功能分组卡片 -->
            <section
                id="features"
                class="border-t bg-muted/40"
            >
                <div class="mx-auto w-full max-w-6xl space-y-14 px-4 py-16 lg:px-6 lg:py-20">
                    <div
                        v-for="group in featureGroups"
                        :key="group.title"
                        class="space-y-6"
                    >
                        <div class="max-w-2xl space-y-2">
                            <h2 class="text-2xl font-bold tracking-tight">
                                {{ group.title }}
                            </h2>
                            <p class="text-sm text-muted-foreground lg:text-base">
                                {{ group.description }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="item in group.items"
                                :key="item.title"
                                class="rounded-xl border bg-card p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                                :data-test="`feature-card-${item.title}`"
                            >
                                <div class="mb-3 inline-flex rounded-lg bg-primary/10 p-2 text-primary">
                                    <component
                                        :is="item.icon"
                                        class="size-5"
                                    />
                                </div>
                                <h3 class="mb-1 font-medium">{{ item.title }}</h3>
                                <p class="text-sm leading-relaxed text-muted-foreground">
                                    {{ item.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA 条 -->
            <section class="mx-auto w-full max-w-6xl px-4 py-16 lg:px-6">
                <div class="flex flex-col items-start justify-between gap-6 rounded-2xl bg-primary px-6 py-10 text-primary-foreground sm:items-center lg:flex-row lg:px-10">
                    <div class="max-w-xl space-y-2">
                        <h2 class="text-2xl font-bold tracking-tight">
                            准备好开始了吗？
                        </h2>
                        <p class="text-sm opacity-90 lg:text-base">
                            注册账号即可进入后台，几分钟内完成初始化配置。
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Button
                            as-child
                            size="lg"
                            variant="secondary"
                            data-test="cta-primary-button"
                        >
                            <Link :href="isAuthenticated ? urls.dashboard : urls.register">
                                {{ isAuthenticated ? '进入后台' : '立即注册' }}
                            </Link>
                        </Button>
                        <span
                            v-if="!isAuthenticated"
                            class="text-sm opacity-90"
                        >
                            已有账号？
                            <Link
                                :href="urls.login"
                                class="underline underline-offset-4"
                            >直接登录</Link>
                        </span>
                    </div>
                </div>
            </section>

            <!-- 反馈支持 -->
            <section
                id="feedback"
                class="border-t bg-muted/40"
            >
                <div class="mx-auto grid w-full max-w-6xl items-center gap-10 px-4 py-16 lg:grid-cols-2 lg:px-6 lg:py-20">
                    <div class="space-y-4">
                        <h2 class="text-3xl font-bold tracking-tight lg:text-4xl">
                            有什么想法，欢迎吐槽
                        </h2>
                        <p class="text-sm text-muted-foreground lg:text-base">
                            无论是功能建议、使用困惑还是发现的缺陷，都欢迎告诉我们，
                            每一条反馈都会被认真对待。
                        </p>
                        <a
                            href="mailto:support@example.com"
                            class="inline-block text-lg font-semibold text-primary underline-offset-4 hover:underline lg:text-xl"
                        >
                            support@example.com
                        </a>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border bg-card p-5 shadow-xs">
                            <div class="mb-3 inline-flex rounded-lg bg-primary/10 p-2 text-primary">
                                <MessagesSquare class="size-5" />
                            </div>
                            <h3 class="mb-1 font-medium">给我们提提意见</h3>
                            <p class="text-sm text-muted-foreground">
                                说说哪里好用、哪里别扭，帮助我们把产品打磨得更好。
                            </p>
                        </div>
                        <div class="rounded-xl border bg-card p-5 shadow-xs">
                            <div class="mb-3 inline-flex rounded-lg bg-primary/10 p-2 text-primary">
                                <Lightbulb class="size-5" />
                            </div>
                            <h3 class="mb-1 font-medium">功能建议</h3>
                            <p class="text-sm text-muted-foreground">
                                缺什么功能直接提，合理的想法会排进开发计划。
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- 页脚 -->
        <footer class="bg-primary text-primary-foreground">
            <div class="mx-auto grid w-full max-w-6xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4 lg:px-6">
                <div class="space-y-3">
                    <div class="flex items-center font-semibold">
                        <AppLogo />
                    </div>
                    <p class="text-sm leading-relaxed opacity-80">
                        简洁、安全、现代化的站点管理后台，
                        让内容与系统管理回归轻松。
                    </p>
                </div>

                <div
                    v-for="column in footerColumns"
                    :key="column.title"
                    class="space-y-3"
                >
                    <h3 class="text-sm font-semibold">{{ column.title }}</h3>
                    <ul class="space-y-2 text-sm opacity-80">
                        <li
                            v-for="link in column.links"
                            :key="link.label"
                        >
                            <a
                                :href="link.href"
                                class="transition-opacity hover:opacity-100 hover:underline"
                            >
                                {{ link.label }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-primary-foreground/20">
                <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-5 text-xs opacity-75 lg:px-6">
                    <span>© {{ new Date().getFullYear() }} {{ $page.props.name }}. 保留所有权利。</span>
                    <span class="inline-flex items-center gap-1">
                        <Lock class="size-3.5" />
                        安全可信 · 持续迭代
                    </span>
                </div>
            </div>
        </footer>

        <!-- 页脚悬浮客服图标（装饰） -->
        <div
            aria-hidden="true"
            class="pointer-events-none fixed bottom-6 left-6 hidden text-primary lg:block"
        >
            <LifeBuoy class="size-6 opacity-40" />
        </div>
    </div>
</template>
