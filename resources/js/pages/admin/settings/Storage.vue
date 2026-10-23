<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import StorageController from '@/actions/App/Http/Controllers/Admin/Settings/StorageController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { edit } from '@/routes/admin/storage';

interface StorageProps {
    disk: string;
    oss_bucket: string;
    oss_endpoint: string;
    oss_region: string;
    oss_access_key_id: string;
    has_secret: boolean;
    oss_url: string;
}

const { storage } = defineProps<{ storage: StorageProps }>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: '存储设置',
                href: edit(),
            },
        ],
    },
});

// 磁盘选择状态：驱动 OSS 参数区块的显隐
const disk = ref(storage.disk);
</script>

<template>
    <Head title="存储设置" />

    <h1 class="sr-only">存储设置</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="存储配置"
            description="配置媒体文件的存储方式，默认本地，可切换 OSS 对象存储"
        />

        <Form
            v-bind="StorageController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <!-- 磁盘选择：值通过隐藏 input 提交 -->
            <input
                type="hidden"
                name="disk"
                :value="disk"
            />
            <div class="grid gap-2">
                <Label>存储方式</Label>
                <Select
                    :model-value="disk"
                    @update:model-value="(v) => (disk = String(v))"
                >
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="选择存储方式" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="local">本地存储</SelectItem>
                        <SelectItem value="oss">OSS 对象存储（S3 兼容）</SelectItem>
                    </SelectContent>
                </Select>
                <p class="text-xs text-muted-foreground">
                    切换后仅影响新上传的文件，已有媒体不受影响
                </p>
                <InputError :message="errors.disk" />
            </div>

            <template v-if="disk === 'oss'">
                <div class="grid gap-2">
                    <Label for="oss_bucket">Bucket</Label>
                    <Input
                        id="oss_bucket"
                        name="oss_bucket"
                        :default-value="storage.oss_bucket"
                        placeholder="如 my-bucket"
                    />
                    <InputError :message="errors.oss_bucket" />
                </div>

                <div class="grid gap-2">
                    <Label for="oss_endpoint">Endpoint</Label>
                    <Input
                        id="oss_endpoint"
                        name="oss_endpoint"
                        :default-value="storage.oss_endpoint"
                        placeholder="如 https://oss-cn-hangzhou.aliyuncs.com"
                    />
                    <InputError :message="errors.oss_endpoint" />
                </div>

                <div class="grid gap-2">
                    <Label for="oss_region">Region</Label>
                    <Input
                        id="oss_region"
                        name="oss_region"
                        :default-value="storage.oss_region"
                        placeholder="区域，部分厂商可留空"
                    />
                    <InputError :message="errors.oss_region" />
                </div>

                <div class="grid gap-2">
                    <Label for="oss_access_key_id">Access Key ID</Label>
                    <Input
                        id="oss_access_key_id"
                        name="oss_access_key_id"
                        :default-value="storage.oss_access_key_id"
                        placeholder="访问密钥 ID"
                    />
                    <InputError :message="errors.oss_access_key_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="oss_secret_access_key">Access Key Secret</Label>
                    <Input
                        id="oss_secret_access_key"
                        type="password"
                        name="oss_secret_access_key"
                        autocomplete="new-password"
                        :placeholder="storage.has_secret ? '已设置，留空则保持不变' : '访问密钥 Secret'"
                    />
                    <p class="text-xs text-muted-foreground">加密存储，不会明文回显</p>
                    <InputError :message="errors.oss_secret_access_key" />
                </div>

                <div class="grid gap-2">
                    <Label for="oss_url">自定义域名（可选）</Label>
                    <Input
                        id="oss_url"
                        name="oss_url"
                        :default-value="storage.oss_url"
                        placeholder="CDN 加速域名，如 https://cdn.example.com"
                    />
                    <InputError :message="errors.oss_url" />
                </div>
            </template>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-storage-button"
                >
                    保存
                </Button>
            </div>
        </Form>
    </div>
</template>
