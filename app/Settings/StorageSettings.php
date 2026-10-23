<?php

namespace App\Settings;

use App\Settings\Concerns\SafeOperations;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

/**
 * 存储配置：控制媒体库写入的磁盘（本地 / OSS S3 兼容协议）
 */
class StorageSettings extends Settings
{
    use SafeOperations;

    /**
     * 媒体存储磁盘：local 使用本地 public 磁盘，oss 使用 S3 兼容对象存储
     */
    public string $disk = 'local';

    /** OSS 桶名 */
    public string $oss_bucket = '';

    /** OSS 端点，如 https://oss-cn-hangzhou.aliyuncs.com */
    public string $oss_endpoint = '';

    /** OSS 区域，如 ap-southeast-1（部分厂商可用空串） */
    public string $oss_region = '';

    /** OSS AccessKey ID */
    public string $oss_access_key_id = '';

    /** OSS AccessKey Secret（加密存储，页面不回显明文） */
    #[ShouldBeEncrypted]
    public string $oss_secret_access_key = '';

    /** 可选的自定义域名 / CDN 加速地址 */
    public string $oss_url = '';

    public static function group(): string
    {
        return 'storage';
    }

    /**
     * OSS 参数是否已填写完整（用于判断磁盘能否切换）
     */
    public function isOssConfigured(): bool
    {
        return $this->oss_bucket !== ''
            && $this->oss_endpoint !== ''
            && $this->oss_access_key_id !== ''
            && $this->oss_secret_access_key !== '';
    }
}
