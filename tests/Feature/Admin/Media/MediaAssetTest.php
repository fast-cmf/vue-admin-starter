<?php

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * 媒体中心功能测试：覆盖访问控制、上传、派生图、裁剪与删除
 */
beforeEach(function () {
    // 伪造 public 磁盘，避免测试污染真实存储
    Storage::fake('public');
});

/**
 * 直接挂载一条视频类型媒体记录
 *
 * 测试中无法伪造可通过 fileinfo 识别为 video/mp4 的内容，
 * 因此绕过集合校验直接建档，仅用于验证类型筛选与派生图跳过逻辑
 */
function createVideoAsset(User $user): MediaAsset
{
    $asset = MediaAsset::create(['title' => 'clip.mp4', 'uploaded_by' => $user->id]);

    $asset->media()->create([
        'uuid' => fake()->uuid(),
        'collection_name' => 'images',
        'name' => 'clip',
        'file_name' => 'clip.mp4',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'mime_type' => 'video/mp4',
        'size' => 102400,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
    ]);

    return $asset;
}

test('未登录用户访问媒体中心会跳转到登录页', function () {
    $this->get(route('admin.media.index'))
        ->assertRedirect(route('login'));
});

test('已登录用户可以上传图片并记录宽高元信息', function () {
    $user = User::factory()->superAdmin()->create();

    actingAs($user)
        ->post(route('admin.media.upload'), [
            'files' => [UploadedFile::fake()->image('test.png', 100, 80)],
        ])
        ->assertRedirect(route('admin.media.index'));

    $asset = MediaAsset::query()->firstOrFail();

    expect($asset->title)->toBe('test.png')
        ->and($asset->uploaded_by)->toBe($user->id)
        ->and($asset->getFirstMedia('images')->getCustomProperty('width'))->toBe(100)
        ->and($asset->getFirstMedia('images')->getCustomProperty('height'))->toBe(80);
});

test('上传白名单之外的文件会被拒绝', function () {
    $user = User::factory()->superAdmin()->create();

    actingAs($user)
        ->post(route('admin.media.upload'), [
            'files' => [UploadedFile::fake()->create('app.exe', 100)],
        ])
        ->assertSessionHasErrors('files.0');

    expect(MediaAsset::count())->toBe(0);
});

test('上传视频不会生成派生图并可通过类型筛选命中', function () {
    $user = User::factory()->superAdmin()->create();

    actingAs($user)->post(route('admin.media.upload'), [
        'files' => [UploadedFile::fake()->image('test.png', 100, 80)],
    ]);
    createVideoAsset($user);

    $videoMedia = MediaAsset::query()->where('title', 'clip.mp4')->firstOrFail()->getFirstMedia('images');

    // 视频不生成派生图，缩略图地址回退到原图
    expect($videoMedia->hasGeneratedConversion('thumb'))->toBeFalse();

    // 类型筛选：video 只命中视频，image 只命中图片
    $assert = fn (string $type, int $count) => $this
        ->actingAs($user)
        ->get(route('admin.media.index', ['type' => $type]))
        ->assertInertia(fn (Assert $page) => $page->component('admin/media/Index')->has('assets.data', $count));

    $assert('video', 1);
    $assert('image', 1);
    $assert('audio', 0);
});

test('列表页输出各类型计数供侧栏筛选展示', function () {
    $user = User::factory()->superAdmin()->create();

    actingAs($user)->post(route('admin.media.upload'), [
        'files' => [
            UploadedFile::fake()->image('a.png'),
            UploadedFile::fake()->image('b.png'),
        ],
    ]);
    createVideoAsset($user);

    $this->actingAs($user)
        ->get(route('admin.media.index'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/media/Index')->where('counts', [
            'all' => 3,
            'image' => 2,
            'video' => 1,
            'audio' => 0,
            'file' => 0,
        ]));
});

test('上传后自动生成 webp 缩略图派生图', function () {
    $user = User::factory()->superAdmin()->create();

    actingAs($user)->post(route('admin.media.upload'), [
        'files' => [UploadedFile::fake()->image('test.png', 100, 80)],
    ]);

    $media = MediaAsset::query()->firstOrFail()->getFirstMedia('images');

    expect($media->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and($media->getUrl('thumb'))->toEndWith('.webp');
});

test('裁剪会保存选区坐标并重建派生图', function () {
    $user = User::factory()->superAdmin()->create();

    actingAs($user)->post(route('admin.media.upload'), [
        'files' => [UploadedFile::fake()->image('test.png', 100, 80)],
    ]);

    $asset = MediaAsset::query()->firstOrFail();

    actingAs($user)
        ->patch(route('admin.media.crop', $asset), [
            'x' => 10,
            'y' => 5,
            'width' => 50,
            'height' => 40,
        ])
        ->assertRedirect(route('admin.media.index'));

    $media = $asset->fresh()->getFirstMedia('images');

    expect($media->getCustomProperty('crop'))->toBe([
        'x' => 10,
        'y' => 5,
        'width' => 50,
        'height' => 40,
    ])
        ->and($media->hasGeneratedConversion('thumb'))->toBeTrue();
});

test('裁剪选区宽高不能为零或负数', function () {
    $user = User::factory()->superAdmin()->create();

    actingAs($user)->post(route('admin.media.upload'), [
        'files' => [UploadedFile::fake()->image('test.png', 100, 80)],
    ]);

    $asset = MediaAsset::query()->firstOrFail();

    actingAs($user)
        ->patch(route('admin.media.crop', $asset), [
            'x' => 0,
            'y' => 0,
            'width' => 0,
            'height' => 0,
        ])
        ->assertSessionHasErrors(['width', 'height']);
});

test('删除媒体条目会同时清理数据行与磁盘文件', function () {
    $user = User::factory()->superAdmin()->create();

    actingAs($user)->post(route('admin.media.upload'), [
        'files' => [UploadedFile::fake()->image('test.png', 100, 80)],
    ]);

    $asset = MediaAsset::query()->firstOrFail();
    $media = $asset->getFirstMedia('images');
    $originalPath = $media->getPath();

    actingAs($user)
        ->delete(route('admin.media.destroy', $asset))
        ->assertRedirect(route('admin.media.index'));

    expect(MediaAsset::count())->toBe(0)
        ->and($media::query()->whereKey($media->id)->count())->toBe(0);

    Storage::disk('public')->assertMissing(str_replace(
        Storage::disk('public')->path('/'),
        '',
        $originalPath,
    ));
});
