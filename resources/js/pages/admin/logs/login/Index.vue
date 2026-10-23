<script setup lang="ts">
import { router, Head } from '@inertiajs/vue3';
import type { RequestPayload } from '@inertiajs/core';
import { Download, Eraser, ScrollText, Trash2 } from '@lucide/vue';
import type { AcceptableValue } from 'reka-ui';
import { toast } from 'vue-sonner';
import { computed, ref, watch } from 'vue';
import LoginLogController from '@/actions/App/Http/Controllers/Admin/Logs/LoginLogController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Heading from '@/components/Heading.vue';
import { can } from '@/lib/can';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationList,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as loginLogIndex } from '@/routes/admin/login-logs';
import type { LoginLogItem, LoginLogPaginator } from '@/types/login-log';

/** 状态筛选下拉的取值，'all' 表示不过滤 */
type StatusFilter = 'all' | 'success' | 'fail';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: '登录日志',
                href: loginLogIndex(),
            },
        ],
    },
});

const { logs } = defineProps<{ logs: LoginLogPaginator }>();

// 筛选状态：从当前 URL 恢复
const search = ref(new URLSearchParams(window.location.search).get('search') ?? '');
const status = ref<StatusFilter>(
    (new URLSearchParams(window.location.search).get('status') as StatusFilter) ?? 'all',
);

// 选中行与弹窗状态：详情 / 删除 / 清空
const selectedIds = ref<number[]>([]);
const viewing = ref<LoginLogItem | null>(null);
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
        status: status.value === 'all' ? undefined : status.value,
        ...overrides,
    };
}

/**
 * 刷新列表（保持状态与滚动位置）
 */
function refreshList(overrides: RequestPayload = {}): void {
    router.get(loginLogIndex.url(), filterQuery(overrides), {
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
 * 切换登录状态筛选
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
function toggleSelectAll(checked: boolean | 'indeterminate'): void {
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
    router.delete(LoginLogController.destroy.url(), {
        data: { ids: deletingIds.value },
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = selectedIds.value.filter(
                (id) => !deletingIds.value.includes(id),
            );
            toast.success('日志已删除');
            deletingIds.value = [];
        },
    });
}

/**
 * 清空全部日志
 */
function confirmClear(): void {
    router.delete(LoginLogController.clear.url(), {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            toast.success('登录日志已清空');
            clearing.value = false;
        },
    });
}

/**
 * 导出 CSV：按当前筛选条件直接触发浏览器下载
 */
function exportLogs(): void {
    window.location.href = LoginLogController.exportMethod.url({
        query: {
            search: search.value || undefined,
            status: status.value === 'all' ? undefined : status.value,
        },
    });
}
</script>

<template>
    <Head title="登录日志" />

    <h1 class="sr-only">登录日志</h1>

    <div class="flex flex-col gap-6 p-4 lg:p-6">
        <!-- 顶部标题区 -->
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="登录日志"
                description="记录后台登录的成功与失败尝试，含账号、IP、浏览器与操作系统信息"
            />
        </div>

        <div class="space-y-5">
            <!-- 工具栏：筛选 + 操作按钮 -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <Input
                        v-model="search"
                        placeholder="搜索用户账号…"
                        class="sm:w-64"
                        data-test="login-log-search-input"
                    />
                    <Select
                        :model-value="status"
                        @update:model-value="selectStatus"
                    >
                        <SelectTrigger class="sm:w-32">
                            <SelectValue placeholder="全部状态" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">全部状态</SelectItem>
                            <SelectItem value="success">成功</SelectItem>
                            <SelectItem value="fail">失败</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        v-if="can('logs.clear')"
                        variant="outline"
                        data-test="clear-login-logs-button"
                        @click="clearing = true"
                    >
                        <Eraser class="size-4" />
                        清空
                    </Button>
                    <Button
                        v-if="can('logs.export')"
                        variant="outline"
                        data-test="export-login-logs-button"
                        @click="exportLogs"
                    >
                        <Download class="size-4" />
                        导出
                    </Button>
                    <Button
                        v-if="can('logs.clear')"
                        variant="destructive"
                        :disabled="selectedIds.length === 0"
                        data-test="delete-login-logs-button"
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
                            <TableHead>用户账号</TableHead>
                            <TableHead>登录状态</TableHead>
                            <TableHead>IP 地址</TableHead>
                            <TableHead class="hidden md:table-cell">浏览器</TableHead>
                            <TableHead class="hidden md:table-cell">操作系统</TableHead>
                            <TableHead>提示信息</TableHead>
                            <TableHead class="hidden lg:table-cell">登录时间</TableHead>
                            <TableHead class="w-16 text-right">操作</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="logs.data.length === 0">
                            <TableCell
                                colspan="9"
                                class="h-32 text-center text-muted-foreground"
                            >
                                <div class="flex flex-col items-center gap-2">
                                    <ScrollText class="size-8" />
                                    <p class="text-sm">暂无登录日志</p>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-for="log in logs.data"
                            :key="log.id"
                            :data-test="`login-log-row-${log.id}`"
                        >
                            <TableCell>
                                <Checkbox
                                    :model-value="selectedIds.includes(log.id)"
                                    @update:model-value="(checked: boolean | 'indeterminate') => toggleSelect(log.id, checked === true)"
                                />
                            </TableCell>
                            <TableCell class="font-medium">{{ log.account }}</TableCell>
                            <TableCell>
                                <Badge
                                    :class="log.status
                                        ? 'bg-success/10 text-success'
                                        : 'bg-destructive/10 text-destructive'"
                                >
                                    {{ log.status ? '成功' : '失败' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="tabular-nums">{{ log.ip }}</TableCell>
                            <TableCell class="hidden md:table-cell">{{ log.browser }}</TableCell>
                            <TableCell class="hidden md:table-cell">{{ log.platform }}</TableCell>
                            <TableCell>{{ log.message }}</TableCell>
                            <TableCell class="hidden whitespace-nowrap lg:table-cell tabular-nums">
                                {{ log.login_at }}
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
                        <template
                            v-for="(item, idx) in items"
                            :key="idx"
                        >
                            <PaginationItem
                                v-if="item.type === 'page'"
                                :value="item.value"
                                :is-active="item.value === logs.current_page"
                            >
                                {{ item.value }}
                            </PaginationItem>
                            <PaginationEllipsis
                                v-else
                                :index="idx"
                            />
                        </template>
                    </PaginationList>
                    <PaginationNext />
                    <span class="ml-2 text-xs text-muted-foreground">
                        第 {{ logs.current_page }} / {{ logs.last_page }} 页，共 {{ logs.total }} 条
                    </span>
                </PaginationContent>
            </Pagination>
        </div>
    </div>

    <!-- 详情弹窗 -->
    <Dialog :open="viewing !== null" @update:open="(v: boolean) => { if (!v) viewing = null; }">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>登录日志详情</DialogTitle>
                <DialogDescription>本次登录尝试的完整信息</DialogDescription>
            </DialogHeader>

            <dl
                v-if="viewing"
                class="overflow-hidden rounded-lg border"
                data-test="login-log-detail"
            >
                <div class="grid grid-cols-3 border-b bg-muted/40">
                    <dt class="col-span-1 px-4 py-2.5 text-sm text-muted-foreground">用户账号</dt>
                    <dd class="col-span-2 px-4 py-2.5 text-sm font-medium">{{ viewing.account }}</dd>
                </div>
                <div class="grid grid-cols-3 border-b">
                    <dt class="col-span-1 px-4 py-2.5 text-sm text-muted-foreground">登录状态</dt>
                    <dd class="col-span-2 px-4 py-2.5">
                        <Badge
                            :class="viewing.status
                                ? 'bg-success/10 text-success'
                                : 'bg-destructive/10 text-destructive'"
                        >
                            {{ viewing.status ? '成功' : '失败' }}
                        </Badge>
                    </dd>
                </div>
                <div class="grid grid-cols-3 border-b bg-muted/40">
                    <dt class="col-span-1 px-4 py-2.5 text-sm text-muted-foreground">IP 地址</dt>
                    <dd class="col-span-2 px-4 py-2.5 text-sm tabular-nums">{{ viewing.ip }}</dd>
                </div>
                <div class="grid grid-cols-3 border-b">
                    <dt class="col-span-1 px-4 py-2.5 text-sm text-muted-foreground">浏览器</dt>
                    <dd class="col-span-2 px-4 py-2.5 text-sm">{{ viewing.browser }}</dd>
                </div>
                <div class="grid grid-cols-3 border-b bg-muted/40">
                    <dt class="col-span-1 px-4 py-2.5 text-sm text-muted-foreground">操作系统</dt>
                    <dd class="col-span-2 px-4 py-2.5 text-sm">{{ viewing.platform }}</dd>
                </div>
                <div class="grid grid-cols-3 border-b">
                    <dt class="col-span-1 px-4 py-2.5 text-sm text-muted-foreground">提示信息</dt>
                    <dd class="col-span-2 px-4 py-2.5 text-sm font-medium">{{ viewing.message }}</dd>
                </div>
                <div class="grid grid-cols-3 bg-muted/40">
                    <dt class="col-span-1 px-4 py-2.5 text-sm text-muted-foreground">登录时间</dt>
                    <dd class="col-span-2 px-4 py-2.5 text-sm tabular-nums">{{ viewing.login_at }}</dd>
                </div>
            </dl>

            <DialogFooter>
                <Button variant="outline" @click="viewing = null">关闭</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- 删除确认弹窗 -->
    <Dialog :open="deletingIds.length > 0" @update:open="(v: boolean) => { if (!v) deletingIds = []; }">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>删除日志</DialogTitle>
                <DialogDescription>
                    确定删除选中的 {{ deletingIds.length }} 条登录日志吗？此操作不可恢复。
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="deletingIds = []">取消</Button>
                <Button variant="destructive" data-test="confirm-delete-login-logs-button" @click="confirmDelete">
                    删除
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- 清空确认弹窗 -->
    <Dialog :open="clearing" @update:open="(v: boolean) => { if (!v) clearing = false; }">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>清空登录日志</DialogTitle>
                <DialogDescription>
                    确定清空全部登录日志吗？所有记录将被永久删除，此操作不可恢复。
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="clearing = false">取消</Button>
                <Button variant="destructive" data-test="confirm-clear-login-logs-button" @click="confirmClear">
                    清空
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
