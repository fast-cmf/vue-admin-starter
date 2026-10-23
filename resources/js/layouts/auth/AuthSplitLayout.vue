<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col overflow-hidden bg-muted p-10 lg:flex dark:border-r"
        >
            <!-- 整栏插画：铺满左侧面板 -->
            <img
                src="/images/login-illustration-v4.png?v=4"
                alt="蓝贝熊管理后台插画"
                class="absolute inset-0 h-full w-full object-cover"
                loading="eager"
            >

            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-semibold text-foreground"
            >
                <AppLogoIcon
                    class="mr-2.5 size-9 rounded-xl bg-white p-1 shadow-lg shadow-black/10"
                />
                {{ name }}
            </Link>

            <!-- 品牌文案：压在插画底部留白区 -->
            <div
                class="absolute inset-x-0 bottom-16 z-20 px-10 text-center"
            >
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                    让站点管理，回归轻松
                </h2>
                <p class="mt-3 text-sm leading-relaxed text-muted-foreground">
                    内容、媒体与系统设置统一管理，简洁、安全、现代化。
                </p>
            </div>

            <!-- 底部信息 -->
            <p class="absolute inset-x-0 bottom-5 z-20 text-center text-xs text-muted-foreground">
                © {{ new Date().getFullYear() }} {{ name }} · 高可用的站点管理框架
            </p>
        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-semibold tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
