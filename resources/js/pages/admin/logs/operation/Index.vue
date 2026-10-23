<script setup lang="ts">
import { router, Head } from "@inertiajs/vue3";
import type { RequestPayload } from "@inertiajs/core";
import { ClipboardList, Download, Eraser, Trash2 } from "@lucide/vue";
import type { AcceptableValue } from "reka-ui";
import { toast } from "vue-sonner";
import { computed, ref, watch } from "vue";
import OperationLogController from "@/actions/App/Http/Controllers/Admin/Logs/OperationLogController";
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
    Select,
    SelectContent,
    SelectItem,
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
import { index as operationLogIndex } from "@/routes/admin/operation-logs";
import type {
    OperationLogItem,
    OperationLogPaginator,
} from "@/types/operation-log";

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: "操作日志",
                href: operationLogIndex(),
            },
        ],
    },
});

const { logs } = defineProps<{ logs: OperationLogPaginator }>();

/** 状态筛选下拉的取值，'all' 表示不过滤 */
type StatusFilter = "all" | "success" | "fail";

// 筛选状态：从当前 URL 恢复
const search = ref(
    new URLSearchParams(window.location.search).get("search") ?? "",
);
const status = ref<StatusFilter>(
    (new URLSearchParams(window.location.search).get(
        "status",
    ) as StatusFilter) ?? "all",
);

// 选中行与弹窗状态：详情 / 删除 / 清空
const selectedIds = ref<number[]>([]);
const viewing = ref<OperationLogItem | null>(null);
const deletingIds = ref<number[]>([]);
const clearing = ref(false);

/** 是否全选当前页 */
const allSelected = computed(
    () => logs.data.length > 0 && selectedIds.value.length === logs.data.length,
);

/**
 * 根据当前筛选生成查询参数
 */
function filterQuery(overrides: RequestPayload = {}): RequestPayload {
    return {
        page: 1,
        search: search.value,
        status: status.value === "all" ? undefined : status.value,
        ...overrides,
    };
}

/**
 * 刷新列表（保持状态与滚动位置）
 */
function refreshList(overrides: RequestPayload = {}): void {
    router.get(operationLogIndex.url(), filterQuery(overrides), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
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
 * 切换操作结果筛选
 */
function selectStatus(value: AcceptableValue): void {
    status.value = value as StatusFilter;
    refreshList();
}

/**
 * 翻页
 */
function goToPage(page: number): void {
    refreshList({ page });
}

/**
 * 全选 / 取消全选当前页
 */
function toggleSelectAll(checked: boolean | "indeterminate"): void {
    selectedIds.value = checked === true ? logs.data.map((log) => log.id) : [];
}

/**
 * 切换单行选中状态
 */
function toggleSelect(id: number, checked: boolean): void {
    selectedIds.value = checked
        ? [...selectedIds.value, id]
        : selectedIds.value.filter((selectedId) => selectedId !== id);
}

/**
 * 删除选中的日志
 */
function confirmDelete(): void {
    if (deletingIds.value.length === 0) {
        return;
    }
    router.delete(OperationLogController.destroy.url(), {
        data: { ids: deletingIds.value },
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = selectedIds.value.filter(
                (id) => !deletingIds.value.includes(id),
            );
            toast.success("日志已删除");
            deletingIds.value = [];
        },
    });
}

/**
 * 清空全部日志
 */
function confirmClear(): void {
    router.delete(OperationLogController.clear.url(), {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            toast.success("操作日志已清空");
            clearing.value = false;
        },
    });
}

/**
 * 格式化请求参数为缩进 JSON，便于详情弹窗展示
 */
function formatParameters(parameters: Record<string, unknown> | null): string {
    return parameters === null ? "" : JSON.stringify(parameters, null, 2);
}

/** 详情弹窗的普通字段：badge 表示渲染状态徽章，full 表示整行展示 */
type DetailField = {
    label: string;
    value: string;
    badge?: boolean;
    full?: boolean;
};

/**
 * 组装详情弹窗的两列字段（请求参数单独通栏渲染，不在此列）
 */
const detailFields = computed<DetailField[]>(() => {
    if (!viewing.value) {
        return [];
    }

    return [
        { label: "日志编号", value: `#${viewing.value.id}` },
        {
            label: "操作结果",
            value: viewing.value.status ? "成功" : "失败",
            badge: true,
        },
        { label: "模块名称", value: viewing.value.module },
        { label: "操作摘要", value: viewing.value.summary },
        { label: "操作类型", value: viewing.value.type },
        { label: "操作人员", value: viewing.value.username },
        { label: "操作时间", value: viewing.value.performed_at },
        { label: "IP 地址", value: viewing.value.ip },
        { label: "耗时", value: `${viewing.value.cost_ms} ms` },
        { label: "请求地址", value: viewing.value.url, full: true },
        {
            label: "错误信息",
            value: viewing.value.error_message ?? "—",
            full: true,
        },
    ];
});

/**
 * 导出 CSV：按当前筛选条件直接触发浏览器下载
 */
function exportLogs(): void {
    window.location.href = OperationLogController.exportMethod.url({
        query: {
            search: search.value || undefined,
            status: status.value === "all" ? undefined : status.value,
        },
    });
}
</script>

<template>
    <Head title="操作日志" />

    <h1 class="sr-only">操作日志</h1>

    <div class="flex flex-col gap-6 p-4 lg:p-6">
        <!-- 顶部标题区 -->
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="操作日志"
                description="记录后台人员的写操作行为，含模块、类型、结果、请求地址与耗时"
            />
        </div>

        <div class="space-y-5">
            <!-- 工具栏：筛选 + 操作按钮 -->
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <Input
                        v-model="search"
                        placeholder="搜索操作人员 / 模块 / 摘要…"
                        class="sm:w-64"
                        data-test="operation-log-search-input"
                    />
                    <Select
                        :model-value="status"
                        @update:model-value="selectStatus"
                    >
                        <SelectTrigger class="sm:w-32">
                            <SelectValue placeholder="全部结果" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">全部结果</SelectItem>
                            <SelectItem value="success">成功</SelectItem>
                            <SelectItem value="fail">失败</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        v-if="can('logs.clear')"
                        variant="outline"
                        data-test="clear-operation-logs-button"
                        @click="clearing = true"
                    >
                        <Eraser class="size-4" />
                        清空
                    </Button>
                    <Button
                        v-if="can('logs.export')"
                        variant="outline"
                        data-test="export-operation-logs-button"
                        @click="exportLogs"
                    >
                        <Download class="size-4" />
                        导出
                    </Button>
                    <Button
                        v-if="can('logs.clear')"
                        variant="destructive"
                        :disabled="selectedIds.length === 0"
                        data-test="delete-operation-logs-button"
                        @click="deletingIds = [...selectedIds]"
                    >
                        <Trash2 class="size-4" />
                        删除
                    </Button>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <Badge variant="secondary">共 {{ logs.total }} 条</Badge>
                <span
                    v-if="selectedIds.length > 0"
                    class="text-xs text-muted-foreground"
                >
                    已选中 {{ selectedIds.length }} 条
                </span>
            </div>

            <!-- 日志表格 -->
            <div class="overflow-hidden rounded-xl border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-12">
                                <Checkbox
                                    :model-value="allSelected"
                                    @update:model-value="toggleSelectAll"
                                />
                            </TableHead>
                            <TableHead class="w-20">日志编号</TableHead>
                            <TableHead>操作结果</TableHead>
                            <TableHead>模块名称</TableHead>
                            <TableHead class="hidden md:table-cell"
                                >操作摘要</TableHead
                            >
                            <TableHead>操作类型</TableHead>
                            <TableHead>操作人员</TableHead>
                            <TableHead class="hidden xl:table-cell"
                                >请求地址</TableHead
                            >
                            <TableHead class="hidden lg:table-cell"
                                >IP 地址</TableHead
                            >
                            <TableHead class="hidden lg:table-cell"
                                >耗时</TableHead
                            >
                            <TableHead class="hidden xl:table-cell"
                                >操作时间</TableHead
                            >
                            <TableHead class="w-16 text-right">操作</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="logs.data.length === 0">
                            <TableCell
                                colspan="12"
                                class="h-32 text-center text-muted-foreground"
                            >
                                <div class="flex flex-col items-center gap-2">
                                    <ClipboardList class="size-8" />
                                    <p class="text-sm">暂无操作日志</p>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-for="log in logs.data"
                            :key="log.id"
                            :data-test="`operation-log-row-${log.id}`"
                        >
                            <TableCell>
                                <Checkbox
                                    :model-value="selectedIds.includes(log.id)"
                                    @update:model-value="
                                        (checked: boolean | 'indeterminate') =>
                                            toggleSelect(
                                                log.id,
                                                checked === true,
                                            )
                                    "
                                />
                            </TableCell>
                            <TableCell
                                class="tabular-nums text-muted-foreground"
                            >
                                #{{ log.id }}
                            </TableCell>
                            <TableCell>
                                <Badge
                                    :class="
                                        log.status
                                            ? 'bg-success/10 text-success'
                                            : 'bg-destructive/10 text-destructive'
                                    "
                                >
                                    {{ log.status ? "成功" : "失败" }}
                                </Badge>
                            </TableCell>
                            <TableCell>{{ log.module }}</TableCell>
                            <TableCell class="hidden md:table-cell">{{
                                log.summary
                            }}</TableCell>
                            <TableCell>{{ log.type }}</TableCell>
                            <TableCell class="font-medium">{{
                                log.username
                            }}</TableCell>
                            <TableCell
                                class="hidden max-w-48 truncate xl:table-cell"
                                :title="log.url"
                            >
                                {{ log.url }}
                            </TableCell>
                            <TableCell
                                class="hidden tabular-nums lg:table-cell"
                                >{{ log.ip }}</TableCell
                            >
                            <TableCell
                                class="hidden whitespace-nowrap lg:table-cell tabular-nums"
                            >
                                {{ log.cost_ms }} ms
                            </TableCell>
                            <TableCell
                                class="hidden whitespace-nowrap xl:table-cell tabular-nums"
                            >
                                {{ log.performed_at }}
                            </TableCell>
                            <TableCell class="text-right">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-muted-foreground"
                                    title="详情"
                                    @click="viewing = log"
                                >
                                    详情
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- 分页 -->
            <Pagination
                v-if="logs.last_page > 1"
                :page="logs.current_page"
                :total="logs.total"
                :items-per-page="logs.per_page"
                @update:page="goToPage"
            >
                <PaginationContent class="flex-wrap justify-center gap-2">
                    <PaginationPrevious />
                    <PaginationList v-slot="{ items }">
                        <template v-for="(item, idx) in items" :key="idx">
                            <PaginationItem
                                v-if="item.type === 'page'"
                                :value="item.value"
                                :is-active="item.value === logs.current_page"
                            >
                                {{ item.value }}
                            </PaginationItem>
                            <PaginationEllipsis v-else :index="idx" />
                        </template>
                    </PaginationList>
                    <PaginationNext />
                    <span class="ml-2 text-xs text-muted-foreground">
                        第 {{ logs.current_page }} / {{ logs.last_page }} 页，共
                        {{ logs.total }} 条
                    </span>
                </PaginationContent>
            </Pagination>
        </div>
    </div>

    <!-- 详情弹窗 -->
    <Dialog
        :open="viewing !== null"
        @update:open="
            (v: boolean) => {
                if (!v) viewing = null;
            }
        "
    >
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>操作日志详情</DialogTitle>
                <DialogDescription>本次操作的完整信息</DialogDescription>
            </DialogHeader>

            <!-- 两列紧凑布局，正常情况下弹窗无需滚动 -->
            <dl
                v-if="viewing"
                class="grid grid-cols-2 gap-2"
                data-test="operation-log-detail"
            >
                <div
                    v-for="field in detailFields"
                    :key="field.label"
                    class="rounded-md bg-muted/40 px-3.5 py-2"
                    :class="field.full ? 'col-span-2' : ''"
                >
                    <dt class="text-xs text-muted-foreground">{{ field.label }}</dt>
                    <dd class="mt-0.5 text-sm leading-5 break-all">
                        <Badge
                            v-if="field.badge"
                            :class="viewing.status
                                ? 'bg-success/10 text-success'
                                : 'bg-destructive/10 text-destructive'"
                        >
                            {{ field.value }}
                        </Badge>
                        <template v-else>{{ field.value }}</template>
                    </dd>
                </div>
                <div
                    v-if="viewing.parameters !== null"
                    class="col-span-2 rounded-md bg-muted/40 px-3.5 py-2"
                >
                    <dt class="text-xs text-muted-foreground">请求参数</dt>
                    <dd class="mt-1">
                        <pre
                            data-test="operation-log-detail-parameters"
                            class="max-h-36 overflow-auto rounded-md border bg-card p-2.5 font-mono text-xs leading-5 break-all whitespace-pre-wrap"
                        >{{ formatParameters(viewing.parameters) }}</pre>
                    </dd>
                </div>
            </dl>

            <DialogFooter>
                <Button variant="outline" @click="viewing = null">关闭</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- 删除确认弹窗 -->
    <Dialog
        :open="deletingIds.length > 0"
        @update:open="
            (v: boolean) => {
                if (!v) deletingIds = [];
            }
        "
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>删除日志</DialogTitle>
                <DialogDescription>
                    确定删除选中的
                    {{ deletingIds.length }} 条操作日志吗？此操作不可恢复。
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="deletingIds = []"
                    >取消</Button
                >
                <Button
                    variant="destructive"
                    data-test="confirm-delete-operation-logs-button"
                    @click="confirmDelete"
                >
                    删除
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- 清空确认弹窗 -->
    <Dialog
        :open="clearing"
        @update:open="
            (v: boolean) => {
                if (!v) clearing = false;
            }
        "
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>清空操作日志</DialogTitle>
                <DialogDescription>
                    确定清空全部操作日志吗？所有记录将被永久删除，此操作不可恢复。
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="clearing = false"
                    >取消</Button
                >
                <Button
                    variant="destructive"
                    data-test="confirm-clear-operation-logs-button"
                    @click="confirmClear"
                >
                    清空
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
