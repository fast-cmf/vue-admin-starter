<?php

namespace App\Http\Requests\Admin\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 裁剪请求：原图坐标系下的选区（x/y 左上角坐标 + 宽高）
 *
 * @property int $x 选区左上角横坐标
 * @property int $y 选区左上角纵坐标
 * @property int $width 选区宽度
 * @property int $height 选区高度
 */
class CropMediaAssetRequest extends FormRequest
{
    /**
     * 验证规则
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'x' => ['required', 'integer', 'min:0'],
            'y' => ['required', 'integer', 'min:0'],
            'width' => ['required', 'integer', 'min:1'],
            'height' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * 允许所有已登录用户裁剪
     */
    public function authorize(): bool
    {
        return true;
    }
}
