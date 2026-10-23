<?php

namespace App\Models;

use App\Settings\StorageSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * 媒体库条目：作为独立实体挂载媒体文件，业务模型通过外键引用（如文章缩略图）
 */
class MediaAsset extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['title', 'folder', 'uploaded_by'];

    /**
     * 媒体条目归属的上传人
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * 当前媒体写入磁盘：存储设置切换为 oss 且参数完整时走 S3 兼容对象存储，否则本地 public
     */
    private function currentDisk(): string
    {
        $settings = app(StorageSettings::class);

        return $settings->disk === 'oss' && $settings->isOssConfigured() ? 'oss' : 'public';
    }

    /**
     * 注册媒体集合：允许图片 / 视频 / 音频 / 常见文档，单个条目挂一个文件
     */
    public function registerMediaCollections(): void
    {
        $disk = $this->currentDisk();

        $this->addMediaCollection('images')
            ->useDisk($disk)
            ->storeConversionsOnDisk($disk)
            ->singleFile()
            ->acceptsMimeTypes([
                // 图片
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
                'image/avif',
                // 视频
                'video/mp4',
                'video/webm',
                'video/quicktime',
                // 音频
                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
                // 文档与压缩包
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
            ]);
    }

    /**
     * 注册派生图：thumb 300 / medium 800，统一 WebP；
     * 若 custom_properties 中存有裁剪坐标则先按坐标裁剪再缩放，原图不变
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        // 派生图只对图片有意义，视频 / 音频 / 文档直接跳过
        if ($media !== null && ! str_starts_with((string) $media->mime_type, 'image/')) {
            return;
        }

        $crop = $media?->custom_properties['crop'] ?? null;

        $applyCrop = function ($conversion) use ($crop) {
            if (is_array($crop)) {
                $width = (int) $crop['width'];
                $height = (int) $crop['height'];
                $centerX = (int) $crop['x'] + intdiv($width, 2);
                $centerY = (int) $crop['y'] + intdiv($height, 2);

                $conversion->focalCrop($width, $height, $centerX, $centerY);
            }

            return $conversion;
        };

        $this->addMediaConversion('thumb')
            ->width(300)
            ->setManipulations($applyCrop)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(800)
            ->setManipulations($applyCrop)
            ->format('webp')
            ->nonQueued();
    }
}
