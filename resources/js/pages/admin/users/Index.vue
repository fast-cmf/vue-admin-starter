<script setup lang="ts">
import { Head, router, usePage } from "@inertiajs/vue3";
import { Ban, Pencil, ShieldCheck, UserPlus, UserRound } from "@lucide/vue";
import { ref, watch } from "vue";
import UserController from "@/actions/App/Http/Controllers/Admin/Users/UserController";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import Heading from "@/components/Heading.vue";
import { Label } from "@/components/ui/label";
import { can } from "@/lib/can";
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationList,
    PaginationNext,
    PaginationPrevious,
} from "@/components/ui/pagination";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { index as usersIndex } from "@/routes/admin/users";
import type {
    RoleOption,
    UserItem,
    UserPaginator,
} from "@/types/rbac";

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: "用户管理",
                href: usersIndex(),
            },
        ],
    },
});

const { users, roles } = defineProps<{
    users: UserPaginator;
    roles: RoleOption[];
}>();

// 筛选状态：从当前 URL 恢复（usePage().url SSR 安全，避免直接访问 window）
const search = ref(
    new URLSearchParams(usePage().url.split("?")[1] ?? "").get("search") ?? "",
);

// 弹窗与提交状态
const assigningUser = ref<UserItem | null>(null);
const selectedRoleIds = ref<number[]>([]);
const saving = ref(false);
const assignError = ref("");

// 新增用户弹窗
const creating = ref(false);
const createForm = ref({ name: "", email: "", password: "" });
const createError = ref("");

// 编辑用户弹窗
const editingUser = ref<UserItem | null>(null);
const editForm = ref({ name: "", email: "", password: "" });
const editError = ref("");

// 拉黑确认弹窗
const blockingUser = ref<UserItem | null>(null);
const blockError = ref("");

/**
 * 刷新列表（保持状态与滚动位置）
 */
function refreshList(overrides: Record<string, unknown> = {}): void {
    router.get(
        usersIndex.url(),
        { page: 1, search: search.value || undefined, ...overrides },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

// 搜索防抖：300ms 后携带关键字刷新列表
let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }
    searchTimer = setTimeout(() => refreshList(), 300);
});

/**
 * 翻页
 */
function goToPage(page: number): void {
    refreshList({ page });
}

/**
 * 打开角色分配弹窗
 */
function openAssign(user: UserItem): void {
    assigningUser.value = user;
    assignError.value = "";
    selectedRoleIds.value = user.roles.map((role) => role.id);
}

/**
 * 切换角色勾选
 */
function toggleRole(roleId: number, checked: boolean | "indeterminate"): void {
    if (checked === true) {
        if (!selectedRoleIds.value.includes(roleId)) {
            selectedRoleIds.value.push(roleId);
        }

        return;
    }

    selectedRoleIds.value = selectedRoleIds.value.filter((id) => id !== roleId);
}

/**
 * 提交角色分配
 */
function submitAssign(): void {
    if (!assigningUser.value) {
        return;
    }

    saving.value = true;
    assignError.value = "";

    router.put(
        UserController.assignRoles.url({ user: assigningUser.value.id }),
        { roles: selectedRoleIds.value },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                assigningUser.value = null;
            },
            onError: (errors) => {
                assignError.value = String(
                    errors.roles ?? "保存失败，请重试。",
                );
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

/**
 * 提交新增用户
 */
function submitCreate(): void {
    saving.value = true;
    createError.value = "";

    router.post(
        UserController.store.url(),
        { ...createForm.value },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                creating.value = false;
                createForm.value = { name: "", email: "", password: "" };
            },
            onError: (errors) => {
                createError.value = String(
                    errors.name ?? errors.email ?? errors.password ?? "创建失败，请重试。",
                );
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

/**
 * 打开编辑用户弹窗
 */
function openEdit(user: UserItem): void {
    editingUser.value = user;
    editForm.value = { name: user.name, email: user.email, password: "" };
    editError.value = "";
}

/**
 * 提交编辑用户（密码留空表示不修改）
 */
function submitEdit(): void {
    if (!editingUser.value) {
        return;
    }

    saving.value = true;
    editError.value = "";

    router.put(
        UserController.update.url({ user: editingUser.value.id }),
        { ...editForm.value },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                editingUser.value = null;
                editForm.value = { name: "", email: "", password: "" };
            },
            onError: (errors) => {
                editError.value = String(
                    errors.name ?? errors.email ?? errors.password ?? "保存失败，请重试。",
                );
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

/**
 * 提交拉黑/解除拉黑
 */
function submitBlock(): void {
    if (!blockingUser.value) {
        return;
    }

    saving.value = true;
    blockError.value = "";

    router.patch(
        UserController.toggleBlock.url({ user: blockingUser.value.id }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                blockingUser.value = null;
            },
            onError: (errors) => {
                blockError.value = String(errors.blocked_at ?? "操作失败，请重试。");
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="用户管理" />

    <h1 class="sr-only">用户管理</h1>

    <div class="flex flex-col gap-6 p-4 lg:p-6">
        <!-- 顶部标题区 -->
        <Heading
            variant="small"
            title="用户管理"
            description="查看后台用户并为其分配角色，角色决定用户可访问的功能"
        />

        <!-- 工具栏：搜索 + 新增 -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <Input
                    v-model="search"
                    placeholder="搜索姓名或邮箱"
                    class="sm:w-64"
                    data-test="user-search-input"
                />
            </div>
            <div class="flex items-center gap-3">
                <Badge
                    variant="secondary"
                    class="w-fit"
                >
                    共 {{ users.total }} 条
                </Badge>
                <Button
                    v-if="can('users.create')"
                    data-test="create-user-button"
                    @click="creating = true"
                >
                    <UserPlus class="size-4" />
                    新增用户
                </Button>
            </div>
        </div>

        <!-- 用户表格 -->
        <div class="overflow-hidden rounded-xl border bg-card">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>用户</TableHead>
                        <TableHead>状态</TableHead>
                        <TableHead class="hidden md:table-cell">邮箱验证</TableHead>
                        <TableHead>角色</TableHead>
                        <TableHead class="hidden lg:table-cell">创建时间</TableHead>
                        <TableHead class="text-right">操作</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="users.data.length === 0">
                        <TableCell
                            colspan="6"
                            class="h-32 text-center text-muted-foreground"
                        >
                            <UserRound class="mx-auto mb-2 size-8" />
                            <p class="text-sm">暂无用户</p>
                        </TableCell>
                    </TableRow>
                    <TableRow
                        v-for="user in users.data"
                        :key="user.id"
                        :data-test="`user-row-${user.id}`"
                    >
                        <TableCell>
                            <div class="flex flex-col">
                                <span class="font-medium">{{ user.name }}</span>
                                <span class="text-xs text-muted-foreground">{{ user.email }}</span>
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge
                                v-if="user.blocked_at"
                                variant="destructive"
                            >
                                已拉黑
                            </Badge>
                            <Badge
                                v-else
                                class="bg-success/10 text-success"
                            >
                                正常
                            </Badge>
                        </TableCell>
                        <TableCell class="hidden md:table-cell">
                            <Badge
                                v-if="user.email_verified_at"
                                class="bg-success/10 text-success"
                            >
                                已验证
                            </Badge>
                            <Badge
                                v-else
                                variant="secondary"
                            >
                                未验证
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="role in user.roles"
                                    :key="role.id"
                                    variant="secondary"
                                >
                                    {{ role.title }}
                                </Badge>
                                <span
                                    v-if="user.roles.length === 0"
                                    class="text-xs text-muted-foreground"
                                >
                                    未分配
                                </span>
                            </div>
                        </TableCell>
                        <TableCell class="hidden lg:table-cell tabular-nums text-muted-foreground">
                            {{ user.created_at }}
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <Button
                                    v-if="can('users.manage')"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-muted-foreground"
                                    title="分配角色"
                                    data-test="assign-roles-button"
                                    @click="openAssign(user)"
                                >
                                    <ShieldCheck class="size-3.5" />
                                    分配角色
                                </Button>
                                <Button
                                    v-if="can('users.update')"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-muted-foreground"
                                    title="编辑用户"
                                    data-test="edit-user-button"
                                    @click="openEdit(user)"
                                >
                                    <Pencil class="size-3.5" />
                                    编辑
                                </Button>
                                <Button
                                    v-if="can('users.block')"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs"
                                    :class="user.blocked_at ? 'text-success' : 'text-destructive'"
                                    :title="user.blocked_at ? '解除拉黑' : '拉黑用户'"
                                    :data-test="user.blocked_at ? 'unblock-user-button' : 'block-user-button'"
                                    @click="blockingUser = user; blockError = ''"
                                >
                                    <Ban class="size-3.5" />
                                    {{ user.blocked_at ? '解除拉黑' : '拉黑' }}
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- 分页 -->
        <div
            v-if="users.last_page > 1"
            class="flex items-center"
        >
            <Pagination
                :page="users.current_page"
                :total="users.total"
                :items-per-page="users.per_page"
                @update:page="goToPage"
            >
                <PaginationContent class="flex-wrap justify-center gap-2">
                    <PaginationPrevious />
                    <PaginationList v-slot="{ items }">
                        <template
                            v-for="(item, index) in items"
                            :key="index"
                        >
                            <PaginationItem
                                v-if="item.type === 'page'"
                                :value="item.value"
                                :is-active="item.value === users.current_page"
                                @click="goToPage(item.value)"
                            >
                                {{ item.value }}
                            </PaginationItem>
                            <PaginationEllipsis
                                v-else
                                :index="index"
                            />
                        </template>
                    </PaginationList>
                    <PaginationNext />
                    <span class="ml-2 text-xs text-muted-foreground">
                        第 {{ users.current_page }} / {{ users.last_page }} 页，共 {{ users.total }} 条
                    </span>
                </PaginationContent>
            </Pagination>
        </div>
    </div>

    <!-- 分配角色弹窗 -->
    <Dialog
        :open="assigningUser !== null"
        @update:open="(open: boolean) => { if (!open) assigningUser = null; }"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>分配角色 · {{ assigningUser?.name }}</DialogTitle>
                <DialogDescription>
                    勾选该用户拥有的角色，保存后立即按新角色生效
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-2">
                <label
                    v-for="role in roles"
                    :key="role.id"
                    class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-muted/60"
                >
                    <Checkbox
                        :model-value="selectedRoleIds.includes(role.id)"
                        :data-test="`assign-role-${role.name}`"
                        @update:model-value="(checked) => toggleRole(role.id, checked)"
                    />
                    <span>{{ role.title }}</span>
                    <span class="ml-auto font-mono text-xs text-muted-foreground">{{ role.name }}</span>
                    <Badge
                        v-if="role.is_builtin"
                        variant="secondary"
                    >
                        内置
                    </Badge>
                </label>
                <p
                    v-if="assignError"
                    class="text-xs text-destructive"
                >
                    {{ assignError }}
                </p>
            </div>

            <DialogFooter>
                <Button
                    variant="outline"
                    data-test="cancel-assign-roles-button"
                    @click="assigningUser = null"
                >
                    取消
                </Button>
                <Button
                    :disabled="saving"
                    data-test="confirm-assign-roles-button"
                    @click="submitAssign"
                >
                    保存
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- 新增用户弹窗 -->
    <Dialog
        :open="creating"
        @update:open="(open: boolean) => { if (!open) creating = false; }"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>新增用户</DialogTitle>
                <DialogDescription>
                    创建后台账号，创建后可在列表中分配角色
                </DialogDescription>
            </DialogHeader>
            <form
                class="space-y-4"
                @submit.prevent="submitCreate"
            >
                <div class="grid gap-2">
                    <Label for="create-name">用户名称</Label>
                    <Input
                        id="create-name"
                        v-model="createForm.name"
                        placeholder="如：张三"
                        data-test="create-name-input"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="create-email">邮箱地址</Label>
                    <Input
                        id="create-email"
                        v-model="createForm.email"
                        type="email"
                        placeholder="name@example.com"
                        data-test="create-email-input"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="create-password">登录密码</Label>
                    <Input
                        id="create-password"
                        v-model="createForm.password"
                        type="password"
                        data-test="create-password-input"
                    />
                    <p
                        v-if="createError"
                        class="text-xs text-destructive"
                    >
                        {{ createError }}
                    </p>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        data-test="cancel-create-user-button"
                        @click="creating = false"
                    >
                        取消
                    </Button>
                    <Button
                        type="submit"
                        :disabled="saving"
                        data-test="confirm-create-user-button"
                    >
                        创建
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- 编辑用户弹窗 -->
    <Dialog
        :open="editingUser !== null"
        @update:open="(open: boolean) => { if (!open) editingUser = null; }"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>编辑用户 · {{ editingUser?.name }}</DialogTitle>
                <DialogDescription>
                    密码留空表示不修改，角色分配请使用「分配角色」
                </DialogDescription>
            </DialogHeader>
            <form
                class="space-y-4"
                @submit.prevent="submitEdit"
            >
                <div class="grid gap-2">
                    <Label for="edit-name">用户名称</Label>
                    <Input
                        id="edit-name"
                        v-model="editForm.name"
                        data-test="edit-name-input"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="edit-email">邮箱地址</Label>
                    <Input
                        id="edit-email"
                        v-model="editForm.email"
                        type="email"
                        data-test="edit-email-input"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="edit-password">重置密码</Label>
                    <Input
                        id="edit-password"
                        v-model="editForm.password"
                        type="password"
                        placeholder="留空表示不修改"
                        data-test="edit-password-input"
                    />
                    <p
                        v-if="editError"
                        class="text-xs text-destructive"
                    >
                        {{ editError }}
                    </p>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        data-test="cancel-edit-user-button"
                        @click="editingUser = null"
                    >
                        取消
                    </Button>
                    <Button
                        type="submit"
                        :disabled="saving"
                        data-test="confirm-edit-user-button"
                    >
                        保存
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- 拉黑/解除拉黑确认弹窗 -->
    <Dialog
        :open="blockingUser !== null"
        @update:open="(open: boolean) => { if (!open) blockingUser = null; }"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>
                    {{ blockingUser?.blocked_at ? '解除拉黑' : '拉黑用户' }} · {{ blockingUser?.name }}
                </DialogTitle>
                <DialogDescription v-if="blockingUser?.blocked_at">
                    解除后该用户可重新使用邮箱密码登录
                </DialogDescription>
                <DialogDescription v-else>
                    拉黑后该用户将立即退出登录且无法再次登录，随时可解除
                </DialogDescription>
            </DialogHeader>
            <p
                v-if="blockError"
                class="text-xs text-destructive"
            >
                {{ blockError }}
            </p>
            <DialogFooter>
                <Button
                    variant="outline"
                    data-test="cancel-block-user-button"
                    @click="blockingUser = null"
                >
                    取消
                </Button>
                <Button
                    :disabled="saving"
                    :variant="blockingUser?.blocked_at ? 'default' : 'destructive'"
                    :data-test="blockingUser?.blocked_at ? 'confirm-unblock-button' : 'confirm-block-button'"
                    @click="submitBlock"
                >
                    {{ blockingUser?.blocked_at ? '解除拉黑' : '确认拉黑' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
