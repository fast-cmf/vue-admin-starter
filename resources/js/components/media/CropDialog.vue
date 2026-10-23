<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Cropper from 'cropperjs';
import type { CropperImage, CropperSelection } from 'cropperjs';
import { toast } from 'vue-sonner';
import { computed, nextTick, ref, watch } from 'vue';
import MediaAssetController from '@/actions/App/Http/Controllers/Admin/Media/MediaAssetController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { MediaAssetItem } from '@/types/media';

const { asset } = defineProps<{ asset: MediaAssetItem | null }>();

const emit = defineEmits<{ close: [] }>();

// 裁剪器与选区状态
const imgEl = ref<HTMLImageElement | null>(null);
const wrapperEl = ref<HTMLDivElement | null>(null);
const cropper = ref<Cropper | null>(null);
const selection = ref<{ x: number; y: number; width: number; height: number } | null>(null);
const ratio = ref<number | null>(null);

// 比例快捷键选项：null 表示自由裁剪
const ratioOptions: { label: string; value: number | null }[] = [
    { label: '自由', value: null },
    { label: '1:1', value: 1 },
    { label: '16:9', value: 16 / 9 },
    { label: '4:3', value: 4 / 3 },
];

// 画布显示尺寸：保持原图宽高比，适配 520×420 上限且不放大，避免出现空白带
const canvasBox = computed(() => {
    const maxW = 520;
    const maxH = 420;
    const w = asset?.width ?? 4;
    const h = asset?.height ?? 3;
    const scale = Math.min(maxW / w, maxH / h, 1);
    return { width: Math.round(w * scale), height: Math.round(h * scale) };
});

// 画布坐标到原图像素的缩放比（画布与原图同比例，可直接换算）
const imageScale = computed(() => {
    const w = asset?.width;
    if (!w || !canvasBox.value.width) {
        return 1;
    }
    return w / canvasBox.value.width;
});

// 当前选区换算为原图像素坐标（用于展示与提交）
const selectionInImage = computed(() => {
    if (!selection.value) {
        return null;
    }
    const k = imageScale.value;
    return {
        x: Math.round(selection.value.x * k),
        y: Math.round(selection.value.y * k),
        width: Math.round(selection.value.width * k),
        height: Math.round(selection.value.height * k),
    };
});

// 弹窗打开且有媒体时初始化裁剪器
watch(
    () => asset?.id,
    async () => {
        destroyCropper();
        if (!asset) {
            return;
        }
        // 先等弹窗内容渲染完成，img 元素挂载后再初始化
        await nextTick();
        await initCropper();
    },
);

/**
 * 等待图片加载完成后初始化裁剪器
 * 画布必须在图片就绪且有确定尺寸时创建，否则选区会得到 0×0 的错误尺寸
 */
async function initCropper(): Promise<void> {
    const img = imgEl.value;
    if (!img) {
        return;
    }
    if (!img.complete) {
        await new Promise<void>((resolve) => {
            const done = (): void => resolve();
            img.addEventListener('load', done, { once: true });
            img.addEventListener('error', done, { once: true });
        });
    }
    // 弹窗打开动画会让容器尺寸短暂抖动，必须等尺寸稳定后初始化，
    // 否则画布按过渡中的小尺寸计算，图片缩放与选区都会错位
    await waitForStableRect(img);
    destroyCropper();
    cropper.value = new Cropper(img);
    setupCanvas();
    fitImageToCanvas();
    resetSelection();
    bindSelectionEvents();
}

/**
 * 画布初始化：移除默认棋盘格背景，并显式钉住与图片等比的像素尺寸
 * （:host 的百分比高度在弹窗内解析异常，必须用内联像素值）
 */
function setupCanvas(): void {
    const canvas = document.querySelector<HTMLElement>('cropper-canvas');
    if (!canvas) {
        return;
    }
    canvas.removeAttribute('background');
    canvas.style.width = `${canvasBox.value.width}px`;
    canvas.style.height = `${canvasBox.value.height}px`;
}

/**
 * 等待元素尺寸稳定（连续两次测量一致且非零），最长 1 秒
 */
async function waitForStableRect(el: HTMLElement): Promise<void> {
    let last = el.getBoundingClientRect();
    for (let i = 0; i < 20; i++) {
        await new Promise((resolve) => setTimeout(resolve, 50));
        const current = el.getBoundingClientRect();
        if (current.width > 0 && current.width === last.width && current.height === last.height) {
            return;
        }
        last = current;
    }
}

/**
 * 让图片按比例缩放并居中填满画布（contain 模式）
 */
function fitImageToCanvas(): void {
    // cropper-image 在 shadow DOM 内，必须走官方 API 获取
    cropper.value?.getCropperImage()?.$center('contain');
}

/**
 * 将选区初始化为画布中央约 80% 的区域
 * 不能依赖选区的 initial-coverage：画布尺寸就绪前它会算出错误的小选区
 */
function resetSelection(): void {
    const sel = cropper.value?.getCropperSelection() as CropperSelection | null;
    if (!sel) {
        return;
    }
    const { width, height } = canvasBox.value;
    sel.width = Math.round(width * 0.8);
    sel.height = Math.round(height * 0.8);
    sel.x = Math.round((width - sel.width) / 2);
    sel.y = Math.round((height - sel.height) / 2);
    syncSelection();
}

/**
 * 监听选区变化，同步坐标与尺寸展示
 */
function bindSelectionEvents(): void {
    const sel = cropper.value?.getCropperSelection();
    if (!sel) {
        return;
    }
    syncSelection();
    sel.addEventListener('change', syncSelection);
}

/**
 * 从裁剪器读取当前选区
 */
function syncSelection(): void {
    const sel = cropper.value?.getCropperSelection();
    if (!sel) {
        return;
    }
    selection.value = {
        x: Math.round(sel.x),
        y: Math.round(sel.y),
        width: Math.round(sel.width),
        height: Math.round(sel.height),
    };
}

/**
 * 切换裁剪比例；NaN 即自由裁剪
 */
function applyRatio(value: number | null): void {
    ratio.value = value;
    const sel = cropper.value?.getCropperSelection();
    if (!sel) {
        return;
    }
    sel.aspectRatio = value ?? Number.NaN;
    syncSelection();
}

/**
 * 销毁裁剪器实例
 */
function destroyCropper(): void {
    cropper.value?.destroy();
    cropper.value = null;
    selection.value = null;
}

/**
 * 提交裁剪坐标到后端并重建派生图
 */
function confirmCrop(): void {
    // 提交前把画布选区坐标换算为原图像素坐标
    if (!asset || !selectionInImage.value) {
        return;
    }
    router.patch(MediaAssetController.crop.url(asset.id), selectionInImage.value, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('裁剪完成，缩略图已更新');
            emit('close');
        },
        onError: () => toast.error('裁剪失败，请重试'),
    });
}
</script>

<template>
    <Dialog :open="asset !== null" @update:open="(v: boolean) => { if (!v) emit('close'); }">
        <DialogContent class="max-h-[90vh] overflow-x-hidden overflow-y-auto sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>裁剪图片</DialogTitle>
                <DialogDescription>
                    {{ asset?.title }}<template v-if="asset?.width && asset?.height"> · {{ asset.width }}×{{ asset.height }}</template>
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-4 sm:flex-row">
                <!-- 裁剪画布：尺寸与原图等比，交由 Cropper 控制缩放 -->
                <div class="flex min-w-0 justify-center">
                    <div
                        ref="wrapperEl"
                        class="max-w-full overflow-hidden rounded-md border bg-muted/50"
                        :style="{ width: `${canvasBox.width}px`, height: `${canvasBox.height}px` }"
                    >
                    <img
                        v-if="asset"
                        :key="asset.id"
                        ref="imgEl"
                        :src="asset.original_url"
                        :alt="asset.title ?? '待裁剪图片'"
                        class="block"
                        data-test="crop-image"
                    />
                    </div>
                </div>

                <!-- 侧栏：比例与选区信息 -->
                <div class="flex w-full flex-col gap-3 sm:w-36">
                    <p class="text-xs text-muted-foreground">裁剪比例</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="option in ratioOptions"
                            :key="option.label"
                            type="button"
                            class="rounded-md border px-2 py-1.5 text-xs transition-colors"
                            :class="ratio === option.value
                                ? 'border-primary bg-primary/10 font-medium text-primary'
                                : 'text-muted-foreground hover:bg-muted'"
                            @click="applyRatio(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">选区尺寸</p>
                    <p class="text-sm" data-test="crop-selection-size">
                        <template v-if="selectionInImage">{{ selectionInImage.width }} × {{ selectionInImage.height }} px</template>
                        <template v-else>拖动图片上的选区</template>
                    </p>
                    <p class="text-xs text-muted-foreground">选区坐标</p>
                    <p class="text-xs" data-test="crop-selection-pos">
                        <template v-if="selectionInImage">x: {{ selectionInImage.x }}, y: {{ selectionInImage.y }}</template>
                        <template v-else>—</template>
                    </p>
                </div>
            </div>

            <DialogFooter>
                <p class="mr-auto text-xs text-muted-foreground">
                    保存坐标后自动重建派生图（WebP），原图文件不变
                </p>
                <Button variant="outline" @click="emit('close')">取消</Button>
                <Button data-test="confirm-crop-button" :disabled="!selection" @click="confirmCrop">
                    确认裁剪
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
/* 画布尺寸由初始化时按原图比例内联指定，这里只保证块级显示 */
:deep(cropper-canvas) {
    display: block;
}
</style>
