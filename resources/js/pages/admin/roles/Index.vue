<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { Pencil, Plus, ShieldCheck, Trash2 } from "@lucide/vue";
import { computed, ref } from "vue";
import RoleController from "@/actions/App/Http/Controllers/Admin/Roles/RoleController";
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
import { can } from "@/lib/can";
import { Label } from "@/components/ui/label";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { index as rolesIndex } from "@/routes/admin/roles";
import type {
    PermissionGroups,
    RoleItem,
} from "@/types/rbac";

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: "角色管理",
                href: rolesIndex(),
            },
        ],
    },
});

const { roles, groups } = defineProps<{
    roles: RoleItem[];
    groups: PermissionGroups;
}>();

// 弹窗状态：权限矩阵 / 新建 / 编辑 / 删除
const matrixRole = ref<RoleItem | null>(null);
const creating = ref(false);
const editingRole = ref<RoleItem | null>(null);
const deletingRole = ref<RoleItem | null>(null);

// 提交状态与表单错误
const saving = ref(false);
const matrixError = ref("");
const nameError = ref("");

// 权限矩阵勾选状态：权限点 -> 是否勾选
const matrixChecked = ref<Record<string, boolean>>({});
// 权限矩阵初始快照，用于判定是否有改动
const matrixInitial = ref<Record<string, boolean>>({});

// 新建/编辑角色共用名称（title），新建另填代码标识（name）
const roleName = ref("");
const roleCode = ref("");

/** 当前权限矩阵下全部权限点 */
const allPermissionNames = computed(() =>
    Object.values(groups).flatMap((items) => Object.keys(items)),
);

/** 矩阵弹窗中是否有未保存改动 */
const matrixDirty = computed(() => {
    return allPermissionNames.value.some(
        (name) => (matrixChecked.value[name] ?? false) !== (matrixInitial.value[name] ?? false),
    );
});

/**
 * 打开权限矩阵弹窗并按角色初始化勾选状态
 */
function openMatrix(role: RoleItem): void {
    matrixRole.value = role;
    matrixError.value = "";

    const initial: Record<string, boolean> = {};
    for (const name of allPermissionNames.value) {
        initial[name] = role.permissions.includes(name);
    }
    matrixChecked.value = { ...initial };
    matrixInitial.value = initial;
}

/**
 * 判断分组勾选状态：全选 / 半选 / 未选（驱动分组复选框的 checked 与 indeterminate）
 */
function groupState(groupName: string): boolean | "indeterminate" {
    const names = Object.keys(groups[groupName]);
    const checkedCount = names.filter((name) => matrixChecked.value[name]).length;

    if (checkedCount === 0) {
        return false;
    }

    return checkedCount === names.length ? true : "indeterminate";
}

/**
 * 全选/取消全选一个分组的全部权限点
 * 半选或未选 → 全选；已全选 → 取消全选
 */
function toggleGroup(groupName: string): void {
    const state = groupState(groupName);
    const target = state !== true;
    for (const name of Object.keys(groups[groupName])) {
        matrixChecked.value[name] = target;
    }
}

/**
 * 保存当前角色的权限矩阵
 */
function saveMatrix(): void {
    if (!matrixRole.value) {
        return;
    }

    saving.value = true;
    matrixError.value = "";

    router.put(
        RoleController.updatePermissions.url({ role: matrixRole.value.id }),
        {
            permissions: allPermissionNames.value.filter(
                (name) => matrixChecked.value[name],
            ),
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                matrixRole.value = null;
            },
            onError: (errors) => {
                matrixError.value = String(errors.permissions ?? "保存失败，请重试。");
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

/**
 * 提交新建角色
 */
function submitCreate(): void {
    saving.value = true;
    nameError.value = "";

    router.post(
        RoleController.store.url(),
        { title: roleName.value, name: roleCode.value },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                creating.value = false;
                roleName.value = "";
                roleCode.value = "";
            },
            onError: (errors) => {
                nameError.value = String(errors.title ?? errors.name ?? "创建失败，请重试。");
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

/**
 * 打开编辑弹窗
 */
function openEdit(role: RoleItem): void {
    editingRole.value = role;
    roleName.value = role.title;
    nameError.value = "";
}

/**
 * 提交编辑角色（仅修改显示名称，标识不可变更）
 */
function submitEdit(): void {
    if (!editingRole.value) {
        return;
    }

    saving.value = true;
    nameError.value = "";

    router.patch(
        RoleController.update.url({ role: editingRole.value.id }),
        { title: roleName.value },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                editingRole.value = null;
                roleName.value = "";
            },
            onError: (errors) => {
                nameError.value = String(errors.title ?? "保存失败，请重试。");
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

/**
 * 提交删除角色
 */
function submitDelete(): void {
    if (!deletingRole.value) {
        return;
    }

    router.delete(RoleController.destroy.url({ role: deletingRole.value.id }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            deletingRole.value = null;
        },
    });
}
</script>

<template>
    <Head title="角色管理" />

    <h1 class="sr-only">角色管理</h1>

    <div class="flex flex-col gap-6 p-4 lg:p-6">
        <!-- 顶部标题区 -->
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="角色管理"
                description="管理后台角色及其权限矩阵，内置角色不可编辑与删除"
            />
            <Button
                v-if="can('roles.create')"
                data-test="create-role-button"
                @click="creating = true"
            >
                <Plus class="size-4" />
                新建角色
            </Button>
        </div>

        <!-- 角色表格 -->
        <div class="overflow-hidden rounded-xl border bg-card">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>角色名称</TableHead>
                        <TableHead class="hidden md:table-cell">角色标识</TableHead>
                        <TableHead class="tabular-nums">用户数</TableHead>
                        <TableHead class="tabular-nums">权限数</TableHead>
                        <TableHead class="hidden md:table-cell">创建时间</TableHead>
                        <TableHead class="w-16 text-right">操作</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="roles.length === 0">
                        <TableCell
                            colspan="6"
                            class="h-32 text-center text-muted-foreground"
                        >
                            <ShieldCheck class="mx-auto mb-2 size-8" />
                            <p class="text-sm">暂无角色，点击右上角「新建角色」创建第一个角色</p>
                        </TableCell>
                    </TableRow>
                    <TableRow
                        v-for="role in roles"
                        :key="role.id"
                        :data-test="`role-row-${role.id}`"
                    >
                        <TableCell>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ role.title }}</span>
                                <Badge
                                    v-if="role.is_builtin"
                                    variant="secondary"
                                >
                                    内置
                                </Badge>
                            </div>
                        </TableCell>
                        <TableCell class="hidden md:table-cell font-mono text-xs text-muted-foreground">
                            {{ role.name }}
                        </TableCell>
                        <TableCell class="tabular-nums">{{ role.users_count }}</TableCell>
                        <TableCell class="tabular-nums">{{ role.permissions_count }}</TableCell>
                        <TableCell class="hidden md:table-cell tabular-nums text-muted-foreground">
                            {{ role.created_at }}
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <Button
                                    v-if="can('roles.permissions')"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-muted-foreground"
                                    title="权限配置"
                                    data-test="matrix-role-button"
                                    @click="openMatrix(role)"
                                >
                                    <ShieldCheck class="size-3.5" />
                                    权限配置
                                </Button>
                                <Button
                                    v-if="can('roles.update')"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-muted-foreground"
                                    title="编辑角色"
                                    :disabled="role.is_builtin"
                                    data-test="edit-role-button"
                                    @click="openEdit(role)"
                                >
                                    <Pencil class="size-3.5" />
                                    编辑
                                </Button>
                                <Button
                                    v-if="can('roles.delete')"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-muted-foreground"
                                    title="删除角色"
                                    :disabled="role.is_builtin"
                                    data-test="delete-role-button"
                                    @click="deletingRole = role"
                                >
                                    <Trash2 class="size-3.5" />
                                    删除
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>

    <!-- 权限矩阵弹窗 -->
    <Dialog
        :open="matrixRole !== null"
        @update:open="(open: boolean) => { if (!open) matrixRole = null; }"
    >
        <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>权限配置 · {{ matrixRole?.title }}</DialogTitle>
                <DialogDescription>
                    按模块勾选该角色可使用的权限，保存后立即生效
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-5">
                <div
                    v-for="(items, groupName) in groups"
                    :key="groupName"
                    class="rounded-xl border p-4"
                >
                    <label class="mb-3 flex cursor-pointer items-center gap-2">
                        <Checkbox
                            :model-value="groupState(groupName as string)"
                            :data-test="`matrix-group-${groupName}`"
                            @update:model-value="() => toggleGroup(groupName as string)"
                        />
                        <span class="text-sm font-medium">{{ groupName }}</span>
                        <span class="text-xs text-muted-foreground">
                            {{ Object.keys(items).filter((name) => matrixChecked[name]).length }}/{{ Object.keys(items).length }}
                        </span>
                    </label>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label
                            v-for="(label, name) in items"
                            :key="name"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-muted/60"
                        >
                            <Checkbox
                                :model-value="matrixChecked[name as string] ?? false"
                                :data-test="`matrix-permission-${name}`"
                                @update:model-value="(checked) => (matrixChecked[name as string] = checked === true)"
                            />
                            <span>{{ label }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button
                    variant="outline"
                    data-test="cancel-matrix-button"
                    @click="matrixRole = null"
                >
                    取消
                </Button>
                <Button
                    :disabled="saving || !matrixDirty"
                    data-test="confirm-matrix-button"
                    @click="saveMatrix"
                >
                    保存
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- 新建角色弹窗 -->
    <Dialog v-model:open="creating">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>新建角色</DialogTitle>
                <DialogDescription>创建角色后可在「权限配置」中为其分配权限</DialogDescription>
            </DialogHeader>
            <form
                class="space-y-4"
                @submit.prevent="submitCreate"
            >
                <div class="grid gap-2">
                    <Label for="role-name">角色名称</Label>
                    <Input
                        id="role-name"
                        v-model="roleName"
                        placeholder="如：内容审核员"
                        data-test="role-name-input"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="role-code">角色标识</Label>
                    <Input
                        id="role-code"
                        v-model="roleCode"
                        placeholder="如：content-reviewer（创建后不可修改）"
                        data-test="role-code-input"
                    />
                    <p
                        v-if="nameError"
                        class="text-xs text-destructive"
                    >
                        {{ nameError }}
                    </p>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        data-test="cancel-create-role-button"
                        @click="creating = false"
                    >
                        取消
                    </Button>
                    <Button
                        type="submit"
                        :disabled="saving"
                        data-test="confirm-create-role-button"
                    >
                        创建
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- 编辑角色弹窗 -->
    <Dialog
        :open="editingRole !== null"
        @update:open="(open: boolean) => { if (!open) editingRole = null; }"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>编辑角色</DialogTitle>
                <DialogDescription>修改角色显示名称，角色标识与已关联的权限、用户不受影响</DialogDescription>
            </DialogHeader>
            <form
                class="space-y-4"
                @submit.prevent="submitEdit"
            >
                <div class="grid gap-2">
                    <Label for="edit-role-name">角色名称</Label>
                    <Input
                        id="edit-role-name"
                        v-model="roleName"
                        data-test="edit-role-name-input"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="edit-role-code">角色标识</Label>
                    <Input
                        id="edit-role-code"
                        :model-value="editingRole?.name"
                        disabled
                        data-test="edit-role-code-input"
                    />
                    <p class="text-xs text-muted-foreground">角色标识创建后不可修改</p>
                    <p
                        v-if="nameError"
                        class="text-xs text-destructive"
                    >
                        {{ nameError }}
                    </p>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        data-test="cancel-edit-role-button"
                        @click="editingRole = null"
                    >
                        取消
                    </Button>
                    <Button
                        type="submit"
                        :disabled="saving"
                        data-test="confirm-edit-role-button"
                    >
                        保存
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- 删除角色确认弹窗 -->
    <Dialog
        :open="deletingRole !== null"
        @update:open="(open: boolean) => { if (!open) deletingRole = null; }"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>删除角色</DialogTitle>
                <DialogDescription>
                    将删除角色「{{ deletingRole?.name }}」，该角色下 {{ deletingRole?.users_count ?? 0 }} 名用户会失去对应权限，此操作不可恢复
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    variant="outline"
                    data-test="cancel-delete-role-button"
                    @click="deletingRole = null"
                >
                    取消
                </Button>
                <Button
                    variant="destructive"
                    data-test="confirm-delete-role-button"
                    @click="submitDelete"
                >
                    确认删除
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
