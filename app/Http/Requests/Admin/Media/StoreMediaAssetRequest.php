<?php

namespace App\Http\Requests\Admin\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 媒体上传请求：允许图片 / 视频 / 音频 / 常见文档，单文件最大 50MB
 */
class StoreMediaAssetRequest extends FormRequest
{
    /**
     * 允许上传的扩展名白名单
     *
     * @return array<int, string>
     */
    public static function allowedExtensions(): array
    {
        return [
            // 图片
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif',
            // 视频
            'mp4', 'webm', 'mov',
            // 音频
            'mp3', 'wav', 'ogg',
            // 文档与压缩包
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip',
        ];
    }

    /**
     * 验证规则
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $extensions = implode(',', self::allowedExtensions());

        return [
            'files' => ['required', 'array'],
            'files.*' => ['required', 'file', 'mimes:'.$extensions, 'max:51200'],
        ];
    }

    /**
     * 允许所有已登录用户上传
     */
    public function authorize(): bool
    {
        return true;
    }
}
