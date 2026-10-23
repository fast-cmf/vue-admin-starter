<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, Upload } from '@lucide/vue';
import { toast } from 'vue-sonner';
import { ref, watch } from 'vue';
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
import { Input } from '@/components/ui/input';
import type { MediaAssetItem } from '@/types/media';

const { open, assets, modelValue } = defineProps<{
    open: boolean;
    assets: MediaAssetItem[];
    modelValue: number | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'update:modelValue': [value: number | null];
}>();

// 当前选中的媒体条目 id
const selected = ref<number | null>(modelValue);
const search = ref('');
const fileInput = ref<HTMLInputElement | null>(null);

// 打开时同步外部值并重置搜索
watch(() => open, (value) => {
    if (value) {
        selected.value = modelValue;
        search.value = '';
    }
});

/**
 * 过滤后的媒体列表（按标题关键字）
 */
function filteredAssets(): MediaAssetItem[] {
    const keyword = search.value.trim().toLowerCase();
    if (!keyword) {
        return assets;
    }
    return assets.filter((item) => (item.title ?? '').toLowerCase().includes(keyword));
}

/**
 * 确认选择：回写选中 id 并关闭弹窗
 */
function confirmPick(): void {
    emit('update:modelValue', selected.value);
    emit('update:open', false);
}

/**
 * 在选图器内直接上传新图，上传成功后列表会随页面刷新
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
        onError: () => toast.error('上传失败，仅支持图片且单文件不超过 10MB'),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="(v: boolean) => emit('update:open', v)">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>从媒体中心选择图片</DialogTitle>
                <DialogDescription>选择一张已有图片，或直接上传新图</DialogDescription>
            </DialogHeader>

            <div class="flex items-center gap-2">
                <Input v-model="search" class="flex-1" placeholder="搜索媒体…" />
                <Button variant="outline" @click="fileInput?.click()">
                    <Upload class="size-4" />
                    上传新图
                </Button>
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    multiple
                    class="hidden"
                    @change="uploadFiles"
                />
            </div>

            <!-- 媒体网格：单选 -->
            <div class="grid grid-cols-3 gap-3">
                <button
                    v-for="item in filteredAssets()"
                    :key="item.id"
                    type="button"
                    class="relative overflow-hidden rounded-lg border-2 text-left transition-colors"
                    :class="selected === item.id ? 'border-primary' : 'border-transparent hover:border-muted-foreground/40'"
                    @click="selected = item.id"
                >
                    <span
                        class="absolute top-1.5 left-1.5 z-10 flex size-5 items-center justify-center rounded-full text-[11px]"
                        :class="selected === item.id ? 'bg-primary text-primary-foreground' : 'bg-muted text-transparent'"
                    >
                        <Check class="size-3" />
                    </span>
                    <span class="block aspect-video bg-muted">
                        <img
                            :src="item.thumb_url"
                            :alt="item.title ?? '媒体文件'"
                            class="size-full object-contain"
                            loading="lazy"
                        />
                    </span>
                    <span class="block truncate border-t px-2 py-1.5 text-xs text-muted-foreground">
                        {{ item.title ?? '未命名' }}
                    </span>
                </button>
            </div>
            <p v-if="assets.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                媒体库为空，请先上传图片
            </p>

            <DialogFooter class="items-center">
                <p class="mr-auto text-xs text-muted-foreground">
                    已选择：<span class="font-medium text-foreground">{{
                        assets.find((item) => item.id === selected)?.title ?? '未选择'
                    }}</span>
                </p>
                <Button variant="outline" @click="emit('update:open', false)">取消</Button>
                <Button data-test="confirm-pick-button" @click="confirmPick">确认选择</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
