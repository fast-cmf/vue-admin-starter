<?php

namespace App\Settings;

use App\Settings\Concerns\SafeOperations;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

/**
 * 邮箱配置：站点发信使用的 SMTP 服务器与发件人信息
 */
class MailSettings extends Settings
{
    use SafeOperations;

    /** 是否启用自定义 SMTP 发信（关闭时使用框架默认 mail 配置） */
    public bool $enabled = false;

    /** 发件人邮箱地址 */
    public string $from_email = '';

    /** 发件人显示名称 */
    public string $from_name = '';

    /** SMTP 服务器地址 */
    public string $smtp_host = '';

    /** SMTP 端口 */
    public int $smtp_port = 587;

    /** SMTP 用户名 */
    public string $smtp_username = '';

    /** SMTP 密码 / 授权码（加密存储，页面不回显明文） */
    #[ShouldBeEncrypted]
    public string $smtp_password = '';

    /** 加密方式：none / ssl / tls */
    public string $smtp_encryption = 'tls';

    public static function group(): string
    {
        return 'mail';
    }

    /**
     * SMTP 参数是否已填写完整（用于判断能否启用发信）
     */
    public function isSmtpConfigured(): bool
    {
        return $this->smtp_host !== ''
            && $this->smtp_username !== ''
            && $this->smtp_password !== '';
    }

    /**
     * SMTP 加密方式映射为 Swift/SMTP 底层参数（none 传 null）
     */
    public function smtpEncryptionScheme(): ?string
    {
        return $this->smtp_encryption === 'none' ? null : $this->smtp_encryption;
    }
}
