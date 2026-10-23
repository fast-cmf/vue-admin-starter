<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import type { RequestPayload } from "@inertiajs/core";
import {
    ChevronDown,
    ChevronRight,
    Circle,
    Eye,
    EyeOff,
    FolderTree,
    Pencil,
    Plus,
    Trash2,
} from "@lucide/vue";
import { computed, ref } from "vue";
import MenuController from "@/actions/App/Http/Controllers/Admin/Menus/MenuController";
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
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { resolveMenuIcon } from "@/lib/menu-icons";
import { index as menusIndex } from "@/routes/admin/menus";
import type {
    MenuFormErrors,
    MenuPageOption,
    MenuParentOption,
    MenuTreeItem,
} from "@/types/menu";

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: "菜单管理",
                href: menusIndex(),
            },
        ],
    },
});

const {
    tree,
    types,
    parents,
    groups,
    icons,
    pages,
} = defineProps<{
    tree: MenuTreeItem[];
    types: Record<string, string>;
    parents: MenuParentOption[];
    groups: Record<string, Record<string, string>>;
    icons: string[];
    pages: MenuPageOption[];
}>();

// 「无（顶级）/无图标/不限制」选项的哨兵值：reka-ui 的 SelectItem 禁止空字符串 value
const NONE = "__none__";

// 展开的目录节点集合（id -> true）
const expanded = ref<Record<number, boolean>>({});

/** 表单弹窗状态：新建 / 编辑 / 删除 */
const creating = ref(false);
const editingMenu = ref<MenuTreeItem | null>(null);
const deletingMenu = ref<MenuTreeItem | null>(null);

const saving = ref(false);
const errors = ref<MenuFormErrors>({});

// 表单字段
const formType = ref("menu");
const formParentId = ref<string>("");
const formTitle = ref("");
const formPath = ref("");
const formIcon = ref("");
const formPermission = ref("");
const formSort = ref("0");
const formVisible = ref(true);

/** 类型徽标配色 */
const typeBadgeVariant = computed(() => {
    return (type: string): "default" | "secondary" | "outline" => {
        if (type === "directory") {
            return "default";
        }

        return type === "menu" ? "secondary" : "outline";
    };
});

/** 树形表格扁平化：目录默认折叠，手动展开后才显示子级 */
const flatRows = computed(() => {
    const rows: Array<{ item: MenuTreeItem; depth: number }> = [];

    const walk = (items: MenuTreeItem[], depth: number): void => {
        for (const item of items) {
            rows.push({ item, depth });

            if (item.children.length > 0 && isExpanded(item)) {
                walk(item.children, depth + 1);
            }
        }
    };

    walk(tree, 0);

    return rows;
});

/** 是否有子菜单（决定展开箭头是否显示） */
function hasChildren(item: MenuTreeItem): boolean {
    return item.children.length > 0;
}

/** 是否展开：仅手动展开过（true）才显示子级，默认折叠 */
function isExpanded(item: MenuTreeItem): boolean {
    return expanded.value[item.id] === true;
}

/** 编辑时不可选的父级：自身及其全部后代（防环，后端同样校验兜底） */
const disabledParentIds = computed(() => {
    const ids = new Set<number>();

    if (editingMenu.value) {
        const collect = (item: MenuTreeItem): void => {
            ids.add(item.id);
            item.children.forEach(collect);
        };

        collect(editingMenu.value);
    }

    return ids;
});

/**
 * 切换目录节点展开状态
 */
function toggleExpand(item: MenuTreeItem): void {
    expanded.value[item.id] = !isExpanded(item);
}

/**
 * 重置表单字段
 */
function resetForm(menu?: MenuTreeItem): void {
    formType.value = menu?.type ?? "menu";
    formParentId.value = menu?.parent_id !== null && menu?.parent_id !== undefined ? String(menu.parent_id) : "";
    formTitle.value = menu?.title ?? "";
    formPath.value = menu?.path ?? "";
    formIcon.value = menu?.icon ?? "";
    formPermission.value = menu?.permission ?? "";
    formSort.value = String(menu?.sort ?? 0);
    formVisible.value = menu?.is_visible ?? true;
    errors.value = {};
}

/**
 * 打开新增弹窗（可携带预选父级：目录下新增菜单，菜单下新增按钮，默认新增目录）
 */
function openCreate(parent?: MenuTreeItem): void {
    resetForm();

    if (parent && parent.type === "directory") {
        formType.value = "menu";
        formParentId.value = String(parent.id);
    } else if (parent && parent.type === "menu") {
        formType.value = "button";
        formParentId.value = String(parent.id);
    } else {
        formType.value = "directory";
    }

    creating.value = true;
}

/**
 * 打开编辑弹窗
 */
function openEdit(menu: MenuTreeItem): void {
    resetForm(menu);
    editingMenu.value = menu;
}

/**
 * 提交新建/编辑菜单
 */
function submit(): void {
    const target = editingMenu.value;
    const payload: RequestPayload = {
        type: formType.value,
        parent_id: formParentId.value === "" ? null : Number(formParentId.value),
        title: formTitle.value,
        // 仅 menu 类型提交 path，其余类型后端禁止该字段
        path: formType.value === "menu" ? (formPath.value === "" ? null : formPath.value) : null,
        icon: formIcon.value === "" ? null : formIcon.value,
        permission: formPermission.value === "" ? null : formPermission.value,
        sort: formSort.value === "" ? 0 : Number(formSort.value),
        is_visible: formVisible.value,
    };

    saving.value = true;
    errors.value = {};

    const options = {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            creating.value = false;
            editingMenu.value = null;
        },
        onError: (err: MenuFormErrors) => {
            errors.value = err;
        },
        onFinish: () => {
            saving.value = false;
        },
    };

    if (target) {
        router.patch(MenuController.update.url({ menu: target.id }), payload, options);
    } else {
        router.post(MenuController.store.url(), payload, options);
    }
}

/**
 * 提交删除菜单
 */
function submitDelete(): void {
    if (!deletingMenu.value) {
        return;
    }

    router.delete(MenuController.destroy.url({ menu: deletingMenu.value.id }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            deletingMenu.value = null;
        },
    });
}
</script>

<template>
    <Head title="菜单管理" />

    <h1 class="sr-only">菜单管理</h1>

    <div class="flex flex-col gap-6 p-4 lg:p-6">
        <!-- 顶部标题区 -->
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="菜单管理"
                description="维护侧边栏菜单树，支持目录、菜单、按钮三种类型，按权限标识控制可见"
            />
            <Button
                v-if="can('menus.create')"
                data-test="create-menu-button"
                @click="openCreate()"
            >
                <Plus class="size-4" />
                新增
            </Button>
        </div>

        <!-- 菜单树表格 -->
        <div class="overflow-hidden rounded-xl border bg-card">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>菜单名称</TableHead>
                        <TableHead class="w-24">类型</TableHead>
                        <TableHead class="hidden md:table-cell">跳转地址</TableHead>
                        <TableHead class="hidden lg:table-cell">权限标识</TableHead>
                        <TableHead class="w-16 text-center tabular-nums">排序</TableHead>
                        <TableHead class="w-20 text-center">可见</TableHead>
                        <TableHead class="w-32 text-right">操作</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="flatRows.length === 0">
                        <TableCell
                            colspan="7"
                            class="h-32 text-center text-muted-foreground"
                        >
                            <FolderTree class="mx-auto mb-2 size-8" />
                            <p class="text-sm">暂无菜单，点击右上角「新增」创建第一个菜单</p>
                        </TableCell>
                    </TableRow>
                    <TableRow
                        v-for="{ item, depth } in flatRows"
                        :key="item.id"
                        :data-test="`menu-row-${item.id}`"
                    >
                        <TableCell>
                            <div
                                class="flex items-center gap-1"
                                :style="{ paddingLeft: `${depth * 1.25}rem` }"
                            >
                                <!-- 展开箭头 -->
                                <button
                                    v-if="hasChildren(item)"
                                    type="button"
                                    class="flex size-5 items-center justify-center rounded text-muted-foreground hover:bg-muted"
                                    :data-test="`expand-menu-${item.id}`"
                                    @click="toggleExpand(item)"
                                >
                                    <ChevronDown
                                        v-if="isExpanded(item)"
                                        class="size-3.5"
                                    />
                                    <ChevronRight
                                        v-else
                                        class="size-3.5"
                                    />
                                </button>
                                <span
                                    v-else
                                    class="size-5"
                                />
                                <!-- 图标 -->
                                <component
                                    :is="item.icon ? resolveMenuIcon(item.icon) : Circle"
                                    v-if="item.icon"
                                    class="size-4 text-muted-foreground"
                                />
                                <span class="font-medium">{{ item.title }}</span>
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge :variant="typeBadgeVariant(item.type)">
                                {{ item.type_label }}
                            </Badge>
                        </TableCell>
                        <TableCell class="hidden max-w-64 truncate text-muted-foreground md:table-cell">
                            {{ item.path ?? "—" }}
                        </TableCell>
                        <TableCell class="hidden text-xs text-muted-foreground lg:table-cell">
                            {{ item.permission ?? "—" }}
                        </TableCell>
                        <TableCell class="text-center tabular-nums">
                            {{ item.sort }}
                        </TableCell>
                        <TableCell class="text-center">
                            <component
                                :is="item.is_visible ? Eye : EyeOff"
                                class="mx-auto size-4"
                                :class="item.is_visible ? 'text-muted-foreground' : 'text-destructive'"
                            />
                        </TableCell>
                        <TableCell class="text-right">
                            <div
                                v-if="can('menus.create') || can('menus.update') || can('menus.delete')"
                                class="flex items-center justify-end gap-1"
                            >
                                <Button
                                    v-if="can('menus.create') && (item.type === 'directory' || item.type === 'menu')"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-muted-foreground"
                                    :title="item.type === 'directory' ? '添加子菜单' : '添加按钮权限标识'"
                                    data-test="create-child-menu-button"
                                    @click="openCreate(item)"
                                >
                                    <Plus class="size-3.5" />
                                    子项
                                </Button>
                                <Button
                                    v-if="can('menus.update')"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-muted-foreground"
                                    title="编辑菜单"
                                    data-test="edit-menu-button"
                                    @click="openEdit(item)"
                                >
                                    <Pencil class="size-3.5" />
                                    编辑
                                </Button>
                                <Button
                                    v-if="can('menus.delete')"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-muted-foreground"
                                    title="删除菜单"
                                    data-test="delete-menu-button"
                                    @click="deletingMenu = item"
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

    <!-- 新建/编辑菜单弹窗 -->
    <Dialog
        :open="creating || editingMenu !== null"
        @update:open="(open: boolean) => { if (!open) { creating = false; editingMenu = null; } }"
    >
        <DialogContent class="sm:max-w-lg max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{ editingMenu ? "编辑菜单" : "新增菜单" }}</DialogTitle>
                <DialogDescription>
                    目录用于组织侧边栏分组，菜单需填写跳转地址，按钮作为页面内的权限标识
                </DialogDescription>
            </DialogHeader>
            <form
                class="space-y-4"
                @submit.prevent="submit"
            >
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="menu-type">菜单类型</Label>
                        <Select
                            :model-value="formType"
                            @update:model-value="(v) => (formType = String(v))"
                        >
                            <SelectTrigger
                                id="menu-type"
                                class="w-full"
                            >
                                <SelectValue placeholder="选择类型" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="(label, value) in types"
                                    :key="value"
                                    :value="String(value)"
                                >
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="errors.type"
                            class="text-xs text-destructive"
                        >
                            {{ errors.type }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="menu-parent">父级</Label>
                        <Select
                            :model-value="formParentId === '' ? NONE : formParentId"
                            @update:model-value="(v) => (formParentId = v === NONE ? '' : String(v))"
                        >
                            <SelectTrigger
                                id="menu-parent"
                                class="w-full"
                            >
                                <SelectValue placeholder="根菜单" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">根菜单</SelectItem>
                                <SelectItem
                                    v-for="parent in parents"
                                    :key="parent.id"
                                    :value="String(parent.id)"
                                    :disabled="disabledParentIds.has(parent.id)"
                                >
                                    <span
                                        class="flex items-center gap-1.5"
                                        :style="{ paddingLeft: parent.depth * 14 + 'px' }"
                                    >
                                        {{ parent.title }}
                                        <span
                                            v-if="parent.type === 'menu'"
                                            class="text-xs text-muted-foreground"
                                        >
                                            菜单
                                        </span>
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="errors.parent_id"
                            class="text-xs text-destructive"
                        >
                            {{ errors.parent_id }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="menu-title">菜单名称</Label>
                    <Input
                        id="menu-title"
                        v-model="formTitle"
                        placeholder="如：内容管理"
                        data-test="menu-title-input"
                    />
                    <p
                        v-if="errors.title"
                        class="text-xs text-destructive"
                    >
                        {{ errors.title }}
                    </p>
                </div>

                <div
                    v-if="formType === 'menu'"
                    class="grid gap-2"
                >
                    <Label for="menu-path">跳转地址</Label>
                    <Select
                        :model-value="formPath === '' ? NONE : formPath"
                        @update:model-value="(v) => (formPath = v === NONE ? '' : String(v))"
                    >
                        <SelectTrigger
                            id="menu-path"
                            data-test="menu-path-select"
                        >
                            <SelectValue placeholder="选择跳转页面" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="page in pages"
                                :key="page.path"
                                :value="page.path"
                            >
                                {{ page.path }}（{{ page.name }}）
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="errors.path"
                        class="text-xs text-destructive"
                    >
                        {{ errors.path }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="menu-permission">权限标识</Label>
                    <Select
                        :model-value="formPermission === '' ? NONE : formPermission"
                        @update:model-value="(v) => (formPermission = v === NONE ? '' : String(v))"
                    >
                        <SelectTrigger
                            id="menu-permission"
                            class="w-full"
                        >
                            <SelectValue placeholder="不限制（登录即可见）" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NONE">不限制（登录即可见）</SelectItem>
                            <SelectGroup
                                v-for="(items, groupName) in groups"
                                :key="groupName"
                            >
                                <SelectLabel>{{ groupName }}</SelectLabel>
                                <SelectItem
                                    v-for="(label, name) in items"
                                    :key="name"
                                    :value="String(name)"
                                >
                                    {{ label }}（{{ name }}）
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="errors.permission"
                        class="text-xs text-destructive"
                    >
                        {{ errors.permission }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="menu-icon">图标</Label>
                        <Select
                            :model-value="formIcon === '' ? NONE : formIcon"
                            @update:model-value="(v) => (formIcon = v === NONE ? '' : String(v))"
                        >
                            <SelectTrigger
                                id="menu-icon"
                                class="w-full"
                            >
                                <SelectValue placeholder="无图标" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">无图标</SelectItem>
                                <SelectItem
                                    v-for="name in icons"
                                    :key="name"
                                    :value="name"
                                >
                                    <span class="flex items-center gap-2">
                                        <component
                                            :is="resolveMenuIcon(name)"
                                            class="size-4"
                                        />
                                        {{ name }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="errors.icon"
                            class="text-xs text-destructive"
                        >
                            {{ errors.icon }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="menu-sort">排序值</Label>
                        <Input
                            id="menu-sort"
                            v-model="formSort"
                            type="number"
                            min="0"
                            max="9999"
                            data-test="menu-sort-input"
                        />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <Checkbox
                        :model-value="formVisible"
                        data-test="menu-visible-checkbox"
                        @update:model-value="(checked) => (formVisible = checked === true)"
                    />
                    在侧边栏中显示
                </label>
                <p
                    v-if="errors.menu"
                    class="text-xs text-destructive"
                >
                    {{ errors.menu }}
                </p>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        data-test="cancel-menu-button"
                        @click="() => { creating = false; editingMenu = null; }"
                    >
                        取消
                    </Button>
                    <Button
                        type="submit"
                        :disabled="saving"
                        data-test="confirm-menu-button"
                    >
                        {{ editingMenu ? "保存" : "创建" }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- 删除菜单确认弹窗 -->
    <Dialog
        :open="deletingMenu !== null"
        @update:open="(open: boolean) => { if (!open) deletingMenu = null; }"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>删除菜单</DialogTitle>
                <DialogDescription>
                    将删除菜单「{{ deletingMenu?.title }}」{{ deletingMenu && deletingMenu.children.length > 0 ? "，该菜单存在子菜单，需先删除子菜单" : "，此操作不可恢复" }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    variant="outline"
                    data-test="cancel-delete-menu-button"
                    @click="deletingMenu = null"
                >
                    取消
                </Button>
                <Button
                    variant="destructive"
                    data-test="confirm-delete-menu-button"
                    @click="submitDelete"
                >
                    确认删除
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
