<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import MailController from '@/actions/App/Http/Controllers/Admin/Settings/MailController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { edit } from '@/routes/admin/mail';

interface MailProps {
    enabled: boolean;
    from_email: string;
    from_name: string;
    smtp_host: string;
    smtp_port: number;
    smtp_username: string;
    has_password: boolean;
    smtp_encryption: string;
}

const { mail } = defineProps<{ mail: MailProps }>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: '邮箱设置',
                href: edit(),
            },
        ],
    },
});

// 启用状态：驱动 SMTP 参数区块的显隐
const enabled = ref(mail.enabled);

// 加密方式：值通过隐藏 input 提交
const encryption = ref(mail.smtp_encryption);
</script>

<template>
    <Head title="邮箱设置" />

    <h1 class="sr-only">邮箱设置</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="邮箱配置"
            description="配置站点发信使用的 SMTP 服务器与发件人信息"
        />

        <Form
            v-bind="MailController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <!-- 启用开关：值通过隐藏 input 提交 -->
            <input
                type="hidden"
                name="enabled"
                :value="enabled ? '1' : '0'"
            />
            <div class="flex items-center gap-2">
                <Checkbox
                    id="enabled"
                    :model-value="enabled"
                    @update:model-value="(v) => (enabled = v === true)"
                />
                <Label for="enabled">启用自定义 SMTP 发信</Label>
            </div>
            <p class="text-xs text-muted-foreground">
                关闭时站点邮件使用框架默认配置发送
            </p>
            <InputError :message="errors.enabled" />

            <template v-if="enabled">
                <div class="grid gap-2">
                    <Label for="from_email">发件人邮箱</Label>
                    <Input
                        id="from_email"
                        name="from_email"
                        type="email"
                        :default-value="mail.from_email"
                        placeholder="如 no-reply@example.com"
                    />
                    <InputError :message="errors.from_email" />
                </div>

                <div class="grid gap-2">
                    <Label for="from_name">发件人名称（可选）</Label>
                    <Input
                        id="from_name"
                        name="from_name"
                        :default-value="mail.from_name"
                        placeholder="收件人看到的发件人显示名"
                    />
                    <InputError :message="errors.from_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="smtp_host">SMTP 服务器</Label>
                    <Input
                        id="smtp_host"
                        name="smtp_host"
                        :default-value="mail.smtp_host"
                        placeholder="如 smtp.example.com"
                    />
                    <InputError :message="errors.smtp_host" />
                </div>

                <div class="grid gap-2">
                    <Label for="smtp_port">端口</Label>
                    <Input
                        id="smtp_port"
                        name="smtp_port"
                        type="number"
                        :default-value="mail.smtp_port"
                        placeholder="如 587"
                    />
                    <InputError :message="errors.smtp_port" />
                </div>

                <div class="grid gap-2">
                    <Label for="smtp_username">用户名</Label>
                    <Input
                        id="smtp_username"
                        name="smtp_username"
                        :default-value="mail.smtp_username"
                        placeholder="登录 SMTP 的账号"
                    />
                    <InputError :message="errors.smtp_username" />
                </div>

                <div class="grid gap-2">
                    <Label for="smtp_password">密码 / 授权码</Label>
                    <Input
                        id="smtp_password"
                        type="password"
                        name="smtp_password"
                        autocomplete="new-password"
                        :placeholder="mail.has_password ? '已设置，留空则保持不变' : '登录 SMTP 的密码或授权码'"
                    />
                    <p class="text-xs text-muted-foreground">加密存储，不会明文回显</p>
                    <InputError :message="errors.smtp_password" />
                </div>

                <!-- 加密方式：值通过隐藏 input 提交 -->
                <input
                    type="hidden"
                    name="smtp_encryption"
                    :value="encryption"
                />
                <div class="grid gap-2">
                    <Label>加密方式</Label>
                    <Select
                        :model-value="encryption"
                        @update:model-value="(v) => (encryption = String(v))"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="选择加密方式" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="tls">TLS（STARTTLS，常用 587）</SelectItem>
                            <SelectItem value="ssl">SSL（常用 465）</SelectItem>
                            <SelectItem value="none">无加密</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.smtp_encryption" />
                </div>
            </template>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-mail-button"
                >
                    保存
                </Button>
            </div>
        </Form>

        <!-- 发送测试邮件：独立于保存表单 -->
        <div
            v-if="enabled"
            class="space-y-3 border-t pt-6"
        >
            <Heading
                variant="small"
                title="发送测试邮件"
                description="使用已保存的 SMTP 配置发送一封测试邮件"
            />
            <Form
                v-bind="MailController.sendTest.form()"
                v-slot="{ errors, processing }"
                class="flex items-start gap-3"
            >
                <div class="grid w-full max-w-sm gap-2">
                    <Input
                        name="test_email"
                        type="email"
                        placeholder="接收测试邮件的邮箱"
                        required
                    />
                    <InputError :message="errors.test_email" />
                </div>
                <Button
                    variant="outline"
                    :disabled="processing"
                    data-test="send-test-mail-button"
                >
                    发送测试邮件
                </Button>
            </Form>
        </div>
    </div>
</template>
