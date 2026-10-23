<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 存储配置更新请求：本地 / OSS 参数校验
 */
class StorageUpdateRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'disk' => ['required', Rule::in(['local', 'oss'])],
            'oss_bucket' => ['nullable', 'string', 'max:255', 'required_if:disk,oss'],
            'oss_endpoint' => ['nullable', 'string', 'max:255', 'required_if:disk,oss'],
            'oss_region' => ['nullable', 'string', 'max:64'],
            'oss_access_key_id' => ['nullable', 'string', 'max:255', 'required_if:disk,oss'],
            'oss_secret_access_key' => ['nullable', 'string', 'max:255'],
            'oss_url' => ['nullable', 'string', 'max:255', 'url'],
        ];
    }
}
