<script setup lang="ts">
import { router, Head } from '@inertiajs/vue3';
import type { RequestPayload } from '@inertiajs/core';
import {
    Clapperboard,
    Copy,
    FileText,
    Images,
    LayoutGrid,
    Music,
    Pencil,
    Scissors,
    Trash2,
    Upload,
} from '@lucide/vue';
import { toast } from 'vue-sonner';
import { computed, ref, watch } from 'vue';
import MediaAssetController from '@/actions/App/Http/Controllers/Admin/Media/MediaAssetController';
import CropDialog from '@/components/media/CropDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { index as mediaIndex } from '@/routes/admin/media';
import type { MediaAssetItem, MediaAssetPaginator, MediaType, MediaTypeCounts } from '@/types/media';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: '媒体中心',
                href: mediaIndex(),
            },
        ],
    },
});

const { assets, counts } = defineProps<{ assets: MediaAssetPaginator; counts: MediaTypeCounts }>();

// 上传与筛选状态
const fileInput = ref<HTMLInputElement | null>(null);
const search = ref(new URLSearchParams(window.location.search).get('search') ?? '');
const activeType = ref<MediaType>((new URLSearchParams(window.location.search).get('type') as MediaType) ?? 'all');

// 弹窗状态：预览 / 裁剪 / 编辑 / 删除
const previewing = ref<MediaAssetItem | null>(null);
const cropping = ref<MediaAssetItem | null>(null);
const editing = ref<MediaAssetItem | null>(null);
const editTitle = ref('');
const editFolder = ref('');
const deleting = ref<MediaAssetItem | null>(null);

/** 左侧类型筛选项配置 */
const typeFilters: Array<{ value: MediaType; label: string; icon: typeof LayoutGrid }> = [
    { value: 'all', label: '全部', icon: LayoutGrid },
    { value: 'image', label: '图片', icon: Images },
    { value: 'video', label: '视频', icon: Clapperboard },
    { value: 'audio', label: '音频', icon: Music },
    { value: 'file', label: '文件', icon: FileText },
];

/** 当前筛选下展示的条目数 */
const filteredTotal = computed(() => (activeType.value === 'all' ? assets.total : counts[activeType.value]));

/**
 * 根据推导出的类型生成查询参数
 */
function filterQuery(overrides: RequestPayload = {}): RequestPayload {
    return {
        page: 1,
        search: search.value,
        type: activeType.value === 'all' ? undefined : activeType.value,
        ...overrides,
    };
}

/**
 * 切换类型筛选
 */
function selectType(type: MediaType): void {
    activeType.value = type;
    router.get(mediaIndex.url(), filterQuery(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

/**
 * 触发系统文件选择框
 */
function openFilePicker(): void {
    fileInput.value?.click();
}

/**
 * 提交批量上传
 */
function uploadFiles(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) {
        return;
    }

    const formData = new FormData();
    Array.from(input.files).forEach((file) => formData.append('files[]', file));

    router.post(MediaAssetController.upload.url(), formData, {
        onSuccess: () => {
            toast.success('上传成功');
            input.value = '';
        },
        onError: () => toast.error('上传失败，仅支持图片 / 视频 / 音频 / 常见文档，单文件不超过 50MB'),
    });
}

/**
 * 复制指定媒体地址到剪贴板（默认原图）
 */
async function copyUrl(asset: MediaAssetItem, url?: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(new URL(url ?? asset.original_url, window.location.origin).href);
        toast.success('已复制图片 URL 到剪贴板');
    } catch {
        toast.error('复制失败，请手动复制');
    }
}

// 搜索防抖：300ms 后携带关键字刷新列表
let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }
    searchTimer = setTimeout(() => {
        router.get(mediaIndex.url(), filterQuery(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300);
});

/**
 * 翻页
 */
function goToPage(page: number): void {
    router.get(mediaIndex.url(), filterQuery({ page }), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

/**
 * 打开编辑弹窗并填充当前值
 */
function openEdit(asset: MediaAssetItem): void {
    editing.value = asset;
    editTitle.value = asset.title ?? '';
    editFolder.value = asset.folder ?? '';
}

/**
 * 保存标题与分组
 */
function saveEdit(): void {
    if (!editing.value) {
        return;
    }
    router.patch(MediaAssetController.update.url(editing.value.id), {
        title: editTitle.value,
        folder: editFolder.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('媒体信息已更新');
            editing.value = null;
        },
    });
}

/**
 * 删除媒体条目
 */
function confirmDelete(): void {
    if (!deleting.value) {
        return;
    }
    router.delete(MediaAssetController.destroy.url(deleting.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('媒体已删除');
            deleting.value = null;
        },
    });
}

/**
 * 格式化文件体积显示
 */
function formatSize(bytes: number): string {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }
    if (bytes >= 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }
    return `${bytes} B`;
}

/**
 * 根据 MIME 推断媒体类型
 */
function assetKind(asset: MediaAssetItem): Exclude<MediaType, 'all'> {
    if (asset.mime_type.startsWith('image/')) {
        return 'image';
    }
    if (asset.mime_type.startsWith('video/')) {
        return 'video';
    }
    if (asset.mime_type.startsWith('audio/')) {
        return 'audio';
    }
    return 'file';
}

/**
 * 提取文件扩展名作为角标文案（图片统一展示 webp）
 */
function extLabel(asset: MediaAssetItem): string {
    if (assetKind(asset) === 'image') {
        return 'webp';
    }
    const dot = asset.title?.lastIndexOf('.');
    return dot !== undefined && dot >= 0 ? (asset.title?.slice(dot + 1) ?? '').toUpperCase() : 'FILE';
}
</script>

<template>
    <Head title="媒体中心" />

    <h1 class="sr-only">媒体中心</h1>

    <div class="flex flex-col gap-6 p-4 lg:p-6">
        <!-- 顶部标题区 -->
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="媒体中心"
                description="派生图自动转换 WebP，原图始终保留，支持图片 / 视频 / 音频 / 文件统一管理"
            />
            <Button
                v-if="can('media.upload')"
                data-test="upload-media-button"
                @click="openFilePicker"
            >
                <Upload class="size-4" />
                上传
            </Button>
            <input
                ref="fileInput"
                type="file"
                multiple
                class="hidden"
                data-test="media-file-input"
                @change="uploadFiles"
            />
        </div>

        <!-- 左右布局：左侧筛选栏 + 右侧媒体网格 -->
        <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">
            <!-- 左侧筛选栏 -->
            <aside class="w-full shrink-0 space-y-5 lg:w-56">
                <div class="space-y-2">
                    <Label class="text-xs text-muted-foreground">搜索</Label>
                    <Input
                        v-model="search"
                        placeholder="搜索文件名 / 标题…"
                        data-test="media-search-input"
                    />
                </div>

                <div class="space-y-2">
                    <Label class="text-xs text-muted-foreground">类型</Label>
                    <nav class="flex flex-row flex-wrap gap-1.5 lg:flex-col lg:gap-1">
                        <button
                            v-for="filter in typeFilters"
                            :key="filter.value"
                            type="button"
                            class="inline-flex items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors lg:w-full"
                            :class="activeType === filter.value
                                ? 'bg-primary/10 font-medium text-primary'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                            :data-test="`media-type-${filter.value}`"
                            @click="selectType(filter.value)"
                        >
                            <component :is="filter.icon" class="size-4" />
                            <span>{{ filter.label }}</span>
                            <span
                                class="ml-auto text-xs tabular-nums"
                                :class="activeType === filter.value ? 'text-primary/70' : 'text-muted-foreground/70'"
                            >
                                {{ counts[filter.value] }}
                            </span>
                        </button>
                    </nav>
                </div>
            </aside>

            <!-- 右侧内容区 -->
            <div class="min-w-0 flex-1 space-y-5">
                <div class="flex items-center justify-between gap-3">
                    <Badge variant="secondary">共 {{ filteredTotal }} 项</Badge>
                </div>

                <!-- 空状态 -->
                <div
                    v-if="assets.data.length === 0"
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed py-20 text-muted-foreground"
                >
                    <Images class="mb-3 size-10" />
                    <p class="text-sm">暂无相关媒体文件，点击右上角上传</p>
                </div>

                <!-- 媒体网格 -->
                <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    <div
                        v-for="asset in assets.data"
                        :key="asset.id"
                        class="group overflow-hidden rounded-xl border bg-card shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                        :data-test="`media-card-${asset.id}`"
                    >
                        <!-- 预览区：点击查看大图 -->
                        <div
                            class="relative aspect-[4/3] cursor-zoom-in overflow-hidden bg-muted"
                            :class="assetKind(asset) !== 'file' && assetKind(asset) !== 'audio' ? '' : 'cursor-default'"
                            @click="assetKind(asset) !== 'file' && assetKind(asset) !== 'audio' && (previewing = asset)"
                        >
                            <!-- 视频预览 -->
                            <video
                                v-if="assetKind(asset) === 'video'"
                                :src="asset.original_url"
                                class="size-full object-cover"
                                preload="metadata"
                                muted
                            />
                            <!-- 图片预览 -->
                            <img
                                v-else-if="assetKind(asset) === 'image'"
                                :src="asset.thumb_url"
                                :alt="asset.title ?? '媒体文件'"
                                class="size-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                                loading="lazy"
                            />
                            <!-- 音频 / 文件占位 -->
                            <div
                                v-else
                                class="flex size-full flex-col items-center justify-center gap-2 text-muted-foreground"
                            >
                                <span class="flex size-12 items-center justify-center rounded-full bg-background">
                                    <Music v-if="assetKind(asset) === 'audio'" class="size-6" />
                                    <FileText v-else class="size-6" />
                                </span>
                                <span class="text-xs">{{ assetKind(asset) === 'audio' ? '音频' : '文件' }}</span>
                            </div>

                            <!-- 复制 URL 按钮：悬停浮现 -->
                            <button
                                type="button"
                                class="absolute top-2 left-2 inline-flex items-center gap-1 rounded-md bg-background/90 px-2 py-1 text-xs text-muted-foreground opacity-0 shadow-sm backdrop-blur transition-all duration-200 group-hover:opacity-100 hover:bg-primary hover:text-primary-foreground"
                                title="复制文件 URL"
                                @click.stop="copyUrl(asset)"
                            >
                                <Copy class="size-3" />
                                复制URL
                            </button>

                            <!-- 扩展名角标 -->
                            <Badge class="absolute right-2 bottom-2" variant="default">{{ extLabel(asset) }}</Badge>
                        </div>

                        <!-- 信息区 -->
                        <div class="space-y-2 p-3">
                            <div>
                                <p class="truncate text-sm font-medium" :title="asset.title ?? ''">
                                    {{ asset.title ?? '未命名' }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    <template v-if="asset.width && asset.height">{{ asset.width }}×{{ asset.height }} · </template>{{ formatSize(asset.size) }}
                                </p>
                            </div>
                            <div class="flex gap-1">
                                <Button
                                    v-if="assetKind(asset) === 'image'"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 flex-1 gap-1 px-2 text-xs text-muted-foreground"
                                    title="裁剪"
                                    @click="cropping = asset"
                                >
                                    <Scissors class="size-3" />
                                    裁剪
                                </Button>
                                <Button
                                    v-if="can('media.update')"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 flex-1 gap-1 px-2 text-xs text-muted-foreground"
                                    title="编辑"
                                    @click="openEdit(asset)"
                                >
                                    <Pencil class="size-3" />
                                    编辑
                                </Button>
                                <Button
                                    v-if="can('media.delete')"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 flex-1 gap-1 px-2 text-xs text-destructive hover:text-destructive"
                                    title="删除"
                                    @click="deleting = asset"
                                >
                                    <Trash2 class="size-3" />
                                    删除
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 分页 -->
                <Pagination
                    v-if="assets.last_page > 1"
                    :page="assets.current_page"
                    :total="assets.total"
                    :items-per-page="assets.per_page"
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
                                    :is-active="item.value === assets.current_page"
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
                            第 {{ assets.current_page }} / {{ assets.last_page }} 页，共 {{ assets.total }} 项
                        </span>
                    </PaginationContent>
                </Pagination>
            </div>
        </div>
    </div>

    <!-- 裁剪弹窗 -->
    <CropDialog
        :asset="cropping"
        @close="cropping = null"
    />

    <!-- 预览弹窗：查看裁剪后的派生图效果 -->
    <Dialog :open="previewing !== null" @update:open="(v: boolean) => { if (!v) previewing = null; }">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle class="pr-6">{{ previewing?.title ?? '媒体预览' }}</DialogTitle>
                <DialogDescription>
                    <template v-if="previewing?.has_crop">已按选区裁剪，下方为裁剪后的派生图（WebP，宽度 800px）</template>
                    <template v-else>媒体预览</template>
                </DialogDescription>
            </DialogHeader>

            <!-- 视频预览 -->
            <div
                v-if="previewing && assetKind(previewing) === 'video'"
                class="overflow-hidden rounded-md border bg-black"
            >
                <video :src="previewing.original_url" controls autoplay class="max-h-[60vh] w-full" />
            </div>
            <!-- 图片预览（medium 派生图，裁剪后即为裁剪效果） -->
            <div v-else-if="previewing" class="grid place-items-center overflow-hidden rounded-md border bg-muted/50">
                <img
                    :src="previewing.medium_url"
                    :alt="previewing.title ?? '媒体预览'"
                    class="max-h-[60vh] w-auto object-contain"
                />
            </div>

            <DialogFooter class="gap-2 sm:justify-between">
                <div class="text-left text-xs text-muted-foreground">
                    <p v-if="previewing?.width && previewing?.height">原图尺寸：{{ previewing.width }} × {{ previewing.height }}</p>
                    <p v-if="previewing">文件大小：{{ formatSize(previewing.size) }}</p>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="previewing && assetKind(previewing) === 'image'"
                        variant="outline"
                        @click="previewing && copyUrl(previewing, previewing.medium_url)"
                    >
                        <Copy class="size-4" />
                        复制裁剪图 URL
                    </Button>
                    <Button variant="outline" @click="previewing && copyUrl(previewing)">
                        <Copy class="size-4" />
                        复制原图 URL
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- 编辑元信息弹窗 -->
    <Dialog :open="editing !== null" @update:open="(v: boolean) => { if (!v) editing = null; }">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>编辑媒体信息</DialogTitle>
                <DialogDescription>修改标题与分组目录</DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-2">
                <div class="grid gap-2">
                    <Label for="media-title">标题</Label>
                    <Input id="media-title" v-model="editTitle" placeholder="媒体标题" />
                </div>
                <div class="grid gap-2">
                    <Label for="media-folder">分组目录</Label>
                    <Input id="media-folder" v-model="editFolder" placeholder="如：banner / 头像" />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="editing = null">取消</Button>
                <Button data-test="save-media-button" @click="saveEdit">保存</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- 删除确认弹窗 -->
    <Dialog :open="deleting !== null" @update:open="(v: boolean) => { if (!v) deleting = null; }">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>删除媒体</DialogTitle>
                <DialogDescription>
                    确定删除「{{ deleting?.title }}」吗？磁盘上的原文件与派生图将一并清理，此操作不可恢复。
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="deleting = null">取消</Button>
                <Button variant="destructive" data-test="confirm-delete-media-button" @click="confirmDelete">删除</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
