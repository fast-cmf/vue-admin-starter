<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\StorageUpdateRequest;
use App\Settings\StorageSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 存储配置控制器：媒体库磁盘（本地 / OSS）参数管理
 */
class StorageController extends Controller
{
    /**
     * 存储配置页：secret 不回显明文，仅返回是否已设置
     */
    public function edit(StorageSettings $settings): Response
    {
        // 加载失败（如 APP_KEY 变更导致解密异常）时回退默认值，避免整页 500
        $settings->loadSafely();

        return Inertia::render('admin/settings/Storage', [
            'storage' => [
                'disk' => $settings->disk,
                'oss_bucket' => $settings->oss_bucket,
                'oss_endpoint' => $settings->oss_endpoint,
                'oss_region' => $settings->oss_region,
                'oss_access_key_id' => $settings->oss_access_key_id,
                'has_secret' => $settings->oss_secret_access_key !== '',
                'oss_url' => $settings->oss_url,
            ],
        ]);
    }

    /**
     * 保存存储配置：secret 提交为空时保留原值，保存后重新注册 oss 磁盘
     */
    public function update(StorageUpdateRequest $request, StorageSettings $settings): RedirectResponse
    {
        // 解密异常（如 APP_KEY 变更）时回退默认值，避免 fill 懒加载时抛出 500
        $settings->loadSafely();

        $data = $request->validated();

        if (($data['oss_secret_access_key'] ?? '') === '') {
            unset($data['oss_secret_access_key']);
        }

        // 保存后剔除与默认值相同的属性行，仅保留用户显式修改过的配置
        $settings->fill($data)->saveWithoutDefaults();

        // 让本次请求的后续操作立即使用新配置
        if ($settings->disk === 'oss' && $settings->isOssConfigured()) {
            config([
                'filesystems.disks.oss' => [
                    'driver' => 's3',
                    'key' => $settings->oss_access_key_id,
                    'secret' => $settings->oss_secret_access_key,
                    'region' => $settings->oss_region !== '' ? $settings->oss_region : 'us-east-1',
                    'bucket' => $settings->oss_bucket,
                    'url' => $settings->oss_url !== '' ? $settings->oss_url : null,
                    'endpoint' => $settings->oss_endpoint,
                    'visibility' => 'public',
                    'throw' => false,
                    'report' => false,
                ],
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('存储配置已更新。')]);

        return to_route('admin.storage.edit');
    }
}
