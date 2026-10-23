<?php

use App\Models\User;
use App\Settings\StorageSettings;
use Illuminate\Support\Facades\DB;

/**
 * 存储配置测试：RefreshDatabase 每个用例自带事务回滚，
 * 这里只需重置容器中的设置实例，避免跨用例持有旧状态
 */
beforeEach(function () {
    app()->forgetInstance(StorageSettings::class);
});

test('storage page is displayed', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('admin.storage.edit'));

    $response->assertOk();
});

test('storage settings can be saved with local disk', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('admin.storage.update'), [
            'disk' => 'local',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.storage.edit'));

    expect(app(StorageSettings::class)->disk)->toBe('local');
});

test('oss settings can be saved and secret is encrypted at rest', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('admin.storage.update'), [
            'disk' => 'oss',
            'oss_bucket' => 'my-bucket',
            'oss_endpoint' => 'https://oss-cn-hangzhou.aliyuncs.com',
            'oss_region' => 'cn-hangzhou',
            'oss_access_key_id' => 'AKID-123',
            'oss_secret_access_key' => 'super-secret-value',
            'oss_url' => 'https://cdn.example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.storage.edit'));

    $settings = app(StorageSettings::class);
    expect($settings->disk)->toBe('oss');
    expect($settings->oss_bucket)->toBe('my-bucket');
    expect($settings->oss_secret_access_key)->toBe('super-secret-value');

    // 数据库中 secret 必须是密文，不落明文
    $stored = DB::table('settings')
        ->where('group', 'storage')
        ->where('name', 'oss_secret_access_key')
        ->value('payload');

    expect($stored)->not->toBe('super-secret-value');
    expect($stored)->not->toContain('super-secret-value');
});

test('oss fields are required when switching to oss', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('admin.storage.edit'))
        ->patch(route('admin.storage.update'), [
            'disk' => 'oss',
            'oss_bucket' => '',
            'oss_endpoint' => '',
            'oss_access_key_id' => '',
        ]);

    $response
        ->assertSessionHasErrors(['oss_bucket', 'oss_endpoint', 'oss_access_key_id'])
        ->assertRedirect(route('admin.storage.edit'));

    expect(app(StorageSettings::class)->disk)->toBe('local');
});

test('empty secret keeps previous value', function () {
    $user = User::factory()->superAdmin()->create();

    // 先设置一次 secret
    $this
        ->actingAs($user)
        ->patch(route('admin.storage.update'), [
            'disk' => 'oss',
            'oss_bucket' => 'my-bucket',
            'oss_endpoint' => 'https://oss-cn-hangzhou.aliyuncs.com',
            'oss_access_key_id' => 'AKID-123',
            'oss_secret_access_key' => 'first-secret',
        ]);

    // 再次保存且 secret 为空，应保留原值
    $this
        ->patch(route('admin.storage.update'), [
            'disk' => 'oss',
            'oss_bucket' => 'my-bucket',
            'oss_endpoint' => 'https://oss-cn-hangzhou.aliyuncs.com',
            'oss_access_key_id' => 'AKID-123',
        ]);

    app()->forgetInstance(StorageSettings::class);

    expect(app(StorageSettings::class)->oss_secret_access_key)->toBe('first-secret');
});

test('guest cannot access storage page', function () {
    $response = $this->get(route('admin.storage.edit'));

    $response->assertRedirect(route('login', absolute: false));
});
