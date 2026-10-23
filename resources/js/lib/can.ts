import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

/** 当前登录用户拥有的权限标识列表（后端 auth.can 共享属性） */
const permissions = computed<string[]>(() => usePage().props.auth.can ?? []);

/**
 * 判断当前用户是否拥有指定权限标识，用于页面按钮显隐控制
 * （与路由 permission: 中间件同一套标识，两层防线保持一致）
 */
export function can(permission: string): boolean {
    return permissions.value.includes(permission);
}
