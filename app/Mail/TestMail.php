<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * SMTP 配置测试邮件：用于后台「发送测试邮件」功能验证发信配置
 */
class TestMail extends Mailable
{
    use SerializesModels;

    /**
     * 创建消息实例
     */
    public function __construct()
    {
        //
    }

    /**
     * 邮件信封：主题与发件人（发件人跟随运行时 mail.from 配置）
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【'.config('app.name').'】SMTP 配置测试邮件',
        );
    }

    /**
     * 邮件内容：纯文本
     */
    public function content(): Content
    {
        return new Content(
            text: '这是一封来自站点后台的测试邮件，收到即说明 SMTP 配置正确。',
        );
    }

    /**
     * 附件：无
     *
     * @return array<int, mixed>
     */
    public function attachments(): array
    {
        return [];
    }
}
