<?php

use App\Models\User;
use App\Settings\StorageSettings;
use Illuminate\Support\Facades\DB;

/**
 * 设置读写加固测试：默认值不落库、解密失败回退默认值
 */
beforeEach(function () {
    app()->forgetInstance(StorageSettings::class);
});

test('properties equal to defaults are not persisted', function () {
    $user = User::factory()->superAdmin()->create();

    $this
        ->actingAs($user)
        ->patch(route('admin.storage.update'), [
            'disk' => 'local',
        ])
        ->assertSessionHasNoErrors();

    // disk 提交值等于默认值，不应落库
    $exists = DB::table('settings')
        ->where('group', 'storage')
        ->where('name', 'disk')
        ->exists();

    expect($exists)->toBeFalse();
    // 读取时回退反射默认值，行为不变
    expect(app(StorageSettings::class)->disk)->toBe('local');
});

test('properties differing from defaults are persisted', function () {
    $user = User::factory()->superAdmin()->create();

    $this
        ->actingAs($user)
        ->patch(route('admin.storage.update'), [
            'disk' => 'oss',
            'oss_bucket' => 'my-bucket',
            'oss_endpoint' => 'https://oss-cn-hangzhou.aliyuncs.com',
            'oss_access_key_id' => 'AKID-123',
            'oss_secret_access_key' => 'super-secret-value',
        ])
        ->assertSessionHasNoErrors();

    $names = DB::table('settings')
        ->where('group', 'storage')
        ->pluck('name')
        ->all();

    expect($names)->toContain('disk', 'oss_bucket', 'oss_secret_access_key');
    expect(app(StorageSettings::class)->disk)->toBe('oss');
});

test('decryption failure falls back to defaults instead of 500', function () {
    $user = User::factory()->superAdmin()->create();

    // 先保存一份含加密项的配置
    $this
        ->actingAs($user)
        ->patch(route('admin.storage.update'), [
            'disk' => 'oss',
            'oss_bucket' => 'my-bucket',
            'oss_endpoint' => 'https://oss-cn-hangzhou.aliyuncs.com',
            'oss_access_key_id' => 'AKID-123',
            'oss_secret_access_key' => 'super-secret-value',
        ])
        ->assertSessionHasNoErrors();

    // 模拟 APP_KEY 变更：旧密文无法解密
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    app()->forgetInstance('encrypter');
    app()->forgetInstance(StorageSettings::class);

    $response = $this
        ->actingAs($user)
        ->get(route('admin.storage.edit'));

    // 不应 500，且该组配置被重置回默认值
    $response->assertOk();

    expect(app(StorageSettings::class)->disk)->toBe('local');
    expect(app(StorageSettings::class)->oss_secret_access_key)->toBe('');

    expect(DB::table('settings')->where('group', 'storage')->count())->toBe(0);
});
