<?php

namespace App\Http\Requests\Admin\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 媒体元信息更新请求：标题与分组目录
 */
class UpdateMediaAssetRequest extends FormRequest
{
    /**
     * 验证规则
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'folder' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * 允许所有已登录用户更新
     */
    public function authorize(): bool
    {
        return true;
    }
}
