<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\MailUpdateRequest;
use App\Mail\TestMail;
use App\Settings\MailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 邮箱配置控制器：站点发信 SMTP 参数管理与测试邮件发送
 */
class MailController extends Controller
{
    /**
     * 邮箱配置页：密码不回显明文，仅返回是否已设置
     */
    public function edit(MailSettings $settings): Response
    {
        // 加载失败（如 APP_KEY 变更导致解密异常）时回退默认值，避免整页 500
        $settings->loadSafely();

        return Inertia::render('admin/settings/Mail', [
            'mail' => [
                'enabled' => $settings->enabled,
                'from_email' => $settings->from_email,
                'from_name' => $settings->from_name,
                'smtp_host' => $settings->smtp_host,
                'smtp_port' => $settings->smtp_port,
                'smtp_username' => $settings->smtp_username,
                'has_password' => $settings->smtp_password !== '',
                'smtp_encryption' => $settings->smtp_encryption,
            ],
        ]);
    }

    /**
     * 保存邮箱配置：密码提交为空时保留原值，保存后立即覆盖运行时 mail 配置
     */
    public function update(MailUpdateRequest $request, MailSettings $settings): RedirectResponse
    {
        // 解密异常（如 APP_KEY 变更）时回退默认值，避免 fill 懒加载时抛出 500
        $settings->loadSafely();

        $data = $request->validated();

        if (($data['smtp_password'] ?? '') === '') {
            unset($data['smtp_password']);
        }

        // 未提交 / 为空的加密方式回落默认 TLS
        $data['smtp_encryption'] = $data['smtp_encryption'] ?? 'tls';

        // 保存后剔除与默认值相同的属性行，仅保留用户显式修改过的配置
        $settings->fill($data)->saveWithoutDefaults();

        // 让本次请求的后续操作立即使用新配置
        $this->applyRuntimeConfig($settings);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('邮箱配置已更新。')]);

        return to_route('admin.mail.edit');
    }

    /**
     * 发送测试邮件：使用当前（保存后）的 SMTP 配置向指定邮箱发信
     */
    public function sendTest(Request $request, MailSettings $settings): RedirectResponse
    {
        // 加载失败时回退默认值，避免解密异常导致 500
        $settings->loadSafely();

        $validated = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        if (! $settings->enabled || ! $settings->isSmtpConfigured()) {
            return back()->withErrors([
                'test_email' => 'SMTP 尚未配置完整，请先保存邮箱配置。',
            ]);
        }

        // 确保使用最新设置发信
        $this->applyRuntimeConfig($settings);

        try {
            Mail::to($validated['test_email'])->send(new TestMail);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'test_email' => '发送失败：'.$e->getMessage(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('测试邮件已发送，请查收。')]);

        return back();
    }

    /**
     * 将设置即时写入运行时 mail 配置（与 AppServiceProvider 逻辑一致）
     */
    protected function applyRuntimeConfig(MailSettings $settings): void
    {
        if (! $settings->enabled || ! $settings->isSmtpConfigured()) {
            return;
        }

        config([
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'host' => $settings->smtp_host,
                'port' => $settings->smtp_port,
                'encryption' => $settings->smtpEncryptionScheme(),
                'username' => $settings->smtp_username,
                'password' => $settings->smtp_password,
                'timeout' => null,
            ],
            'mail.from.address' => $settings->from_email,
            'mail.from.name' => $settings->from_name !== '' ? $settings->from_name : config('app.name'),
        ]);
    }
}
