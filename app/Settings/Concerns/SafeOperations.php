<?php

namespace App\Settings\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * 设置读写加固：
 * 1. loadSafely：APP_KEY 变更后加密项解密失败（The MAC is invalid）时，清空该组配置回退默认值，避免设置页 500
 * 2. saveWithoutDefaults：保存后剔除与属性默认值相同的落库行，数据库中仅保留用户显式修改过的配置
 */
trait SafeOperations
{
    /**
     * 安全加载设置：解密失败时重置该组配置为默认值
     */
    public function loadSafely(): static
    {
        try {
            // toArray 会触发完整加载（含加密项解密）
            $this->toArray();
        } catch (DecryptException) {
            DB::table('settings')->where('group', static::group())->delete();

            $this->clearSettingsCache();
            $this->refresh();
        }

        return $this;
    }

    /**
     * 保存设置并剔除与默认值相同的属性行
     */
    public function saveWithoutDefaults(): static
    {
        $this->save();

        foreach ($this->settingsConfig()->getReflectedProperties() as $name => $property) {
            if ($this->{$name} === $property->getDefaultValue()) {
                DB::table('settings')
                    ->where('group', static::group())
                    ->where('name', $name)
                    ->delete();
            }
        }

        $this->clearSettingsCache();

        return $this;
    }

    /**
     * 清理设置载荷缓存（缓存默认关闭，仅在启用时执行）
     */
    protected function clearSettingsCache(): void
    {
        if (config('settings.cache.enabled')) {
            Artisan::call('settings:clear-cache');
        }
    }
}
