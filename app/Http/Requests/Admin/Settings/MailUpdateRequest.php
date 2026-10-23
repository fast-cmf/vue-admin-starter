<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 邮箱配置更新请求：SMTP 服务器与发件人信息校验
 */
class MailUpdateRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'from_email' => ['nullable', 'string', 'email', 'max:255', 'required_if:enabled,1'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'smtp_host' => ['nullable', 'string', 'max:255', 'required_if:enabled,1'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535', 'required_if:enabled,1'],
            'smtp_username' => ['nullable', 'string', 'max:255', 'required_if:enabled,1'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', Rule::in(['none', 'ssl', 'tls'])],
        ];
    }
}
