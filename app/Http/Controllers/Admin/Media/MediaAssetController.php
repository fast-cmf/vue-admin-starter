<?php

namespace App\Http\Controllers\Admin\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Media\CropMediaAssetRequest;
use App\Http\Requests\Admin\Media\StoreMediaAssetRequest;
use App\Http\Requests\Admin\Media\UpdateMediaAssetRequest;
use App\Models\MediaAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * 媒体中心控制器：列表、上传、编辑、裁剪、删除
 */
class MediaAssetController extends Controller
{
    /**
     * 支持的类型筛选项
     */
    private const TYPE_FILTERS = ['image', 'video', 'audio', 'file'];

    /**
     * 媒体列表页：分页输出每个条目的原图/缩略图地址与元信息，支持类型筛选与关键字搜索
     */
    public function index(Request $request): Response
    {
        $type = $request->query('type');
        $search = trim((string) $request->query('search', ''));

        $assets = MediaAsset::query()
            ->with('media')
            ->when($search !== '', function ($query) use ($search) {
                // 按条目标题或原始文件名模糊匹配
                $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('media', fn ($mediaQuery) => $mediaQuery->where('file_name', 'like', "%{$search}%"));
            })
            ->when(in_array($type, self::TYPE_FILTERS, true), function ($query) use ($type) {
                $query->whereHas('media', fn ($mediaQuery) => $this->applyTypeCondition($mediaQuery, $type));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (MediaAsset $asset) => $this->mapAsset($asset));

        return Inertia::render('admin/media/Index', [
            'assets' => $assets,
            'counts' => $this->typeCounts(),
        ]);
    }

    /**
     * 在媒体表查询上应用类型过滤；"file" 表示图片/视频/音频之外的文件
     */
    private function applyTypeCondition($query, string $type): void
    {
        if ($type === 'image' || $type === 'video' || $type === 'audio') {
            $query->where('mime_type', 'like', $type.'/%');

            return;
        }

        $query->where('mime_type', 'not like', 'image/%')
            ->where('mime_type', 'not like', 'video/%')
            ->where('mime_type', 'not like', 'audio/%');
    }

    /**
     * 统计各类型的媒体数量，用于侧栏筛选项徽标
     *
     * @return array{all: int, image: int, video: int, audio: int, file: int}
     */
    private function typeCounts(): array
    {
        $rows = Media::query()
            ->selectRaw("CASE
                WHEN mime_type LIKE 'image/%' THEN 'image'
                WHEN mime_type LIKE 'video/%' THEN 'video'
                WHEN mime_type LIKE 'audio/%' THEN 'audio'
                ELSE 'file' END AS type_group")
            ->selectRaw('COUNT(*) AS total')
            ->from('media')
            ->groupBy('type_group')
            ->pluck('total', 'type_group');

        $counts = [
            'all' => (int) array_sum($rows->toArray()),
            'image' => (int) ($rows['image'] ?? 0),
            'video' => (int) ($rows['video'] ?? 0),
            'audio' => (int) ($rows['audio'] ?? 0),
            'file' => (int) ($rows['file'] ?? 0),
        ];

        return $counts;
    }

    /**
     * 批量上传图片：每个文件创建一个媒体条目，记录宽高元信息
     */
    public function upload(StoreMediaAssetRequest $request): RedirectResponse
    {
        foreach ($request->file('files', []) as $file) {
            $asset = MediaAsset::create([
                'title' => $file->getClientOriginalName(),
                'uploaded_by' => $request->user()->id,
            ]);

            $media = $asset->addMedia($file)->toMediaCollection('images');
            $this->storeImageDimensions($media);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('上传成功。')]);

        return to_route('admin.media.index');
    }

    /**
     * 更新媒体条目的标题与分组目录
     */
    public function update(UpdateMediaAssetRequest $request, MediaAsset $mediaAsset): RedirectResponse
    {
        $mediaAsset->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('媒体信息已更新。')]);

        return to_route('admin.media.index');
    }

    /**
     * 裁剪：保存选区坐标到 custom_properties 并强制重建派生图（原图不变）
     */
    public function crop(CropMediaAssetRequest $request, MediaAsset $mediaAsset, FileManipulator $fileManipulator): RedirectResponse
    {
        $media = $mediaAsset->getFirstMedia('images');

        abort_unless($media !== null, 404, '媒体文件不存在');

        $crop = $this->clampCrop($media, $request->validated());

        $media->setCustomProperty('crop', $crop)->save();

        // 强制重建全部派生图，使裁剪坐标生效
        $fileManipulator->createDerivedFiles($media);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('裁剪完成，缩略图已更新。')]);

        return to_route('admin.media.index');
    }

    /**
     * 删除媒体条目：InteractsWithMedia 会级联删除 media 行与磁盘文件
     */
    public function destroy(MediaAsset $mediaAsset): RedirectResponse
    {
        $mediaAsset->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('媒体已删除。')]);

        return to_route('admin.media.index');
    }

    /**
     * 将媒体条目映射为前端输出结构
     *
     * @return array{id: int, title: string|null, folder: string|null, size: int, mime_type: string, width: int|null, height: int|null, original_url: string, thumb_url: string, medium_url: string, has_crop: bool}
     */
    protected function mapAsset(MediaAsset $asset): array
    {
        $media = $asset->getFirstMedia('images');

        // 派生图会被裁剪覆盖为同名文件，附加媒体行更新时间作为版本号，避免浏览器缓存旧图
        $version = $media?->updated_at?->timestamp ?? 0;

        return [
            'id' => $asset->id,
            'title' => $asset->title,
            'folder' => $asset->folder,
            'size' => $media?->size ?? 0,
            'mime_type' => $media?->mime_type ?? '',
            'width' => $media?->getCustomProperty('width'),
            'height' => $media?->getCustomProperty('height'),
            'original_url' => $media?->getUrl() ?? '',
            'thumb_url' => $media !== null && $media->hasGeneratedConversion('thumb')
                ? $media->getUrl('thumb').'?v='.$version
                : ($media?->getUrl() ?? ''),
            'medium_url' => $media !== null && $media->hasGeneratedConversion('medium')
                ? $media->getUrl('medium').'?v='.$version
                : ($media?->getUrl() ?? ''),
            'has_crop' => $media?->hasCustomProperty('crop') ?? false,
        ];
    }

    /**
     * 上传后读取图片宽高写入 custom_properties
     */
    protected function storeImageDimensions(Media $media): void
    {
        $info = @getimagesize($media->getPath());

        if ($info !== false) {
            $media->setCustomProperty('width', $info[0])
                ->setCustomProperty('height', $info[1])
                ->save();
        }
    }

    /**
     * 按原图尺寸边界裁剪保护：保证选区不超出图片范围
     *
     * @param  array{x: int, y: int, width: int, height: int}  $crop
     * @return array{x: int, y: int, width: int, height: int}
     */
    protected function clampCrop(Media $media, array $crop): array
    {
        $info = @getimagesize($media->getPath());

        if ($info === false) {
            return $crop;
        }

        $crop['width'] = min($crop['width'], $info[0]);
        $crop['height'] = min($crop['height'], $info[1]);
        $crop['x'] = min($crop['x'], $info[0] - $crop['width']);
        $crop['y'] = min($crop['y'], $info[1] - $crop['height']);

        return $crop;
    }
}
