<?php

use App\Models\OperationLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;

test('operation log list page is displayed', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('admin.operation-logs.index'));

    $response->assertOk();
});

test('guest cannot access operation log list', function () {
    $response = $this->get(route('admin.operation-logs.index'));

    $response->assertRedirect(route('login'));
});

test('admin write operations are recorded automatically', function () {
    Mail::fake();

    $user = User::factory()->superAdmin()->create();

    // 先保存一份已启用的 SMTP 配置，保证 sendTest 走成功路径
    $this
        ->actingAs($user)
        ->patch(route('admin.mail.update'), [
            'enabled' => '1',
            'from_email' => 'no-reply@example.com',
            'from_name' => '站点机器人',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_username' => 'smtp-user',
            'smtp_password' => 'smtp-pass',
            'smtp_encryption' => 'tls',
        ]);

    $this
        ->actingAs($user)
        ->post(route('admin.mail.sendTest'), ['test_email' => 'test@example.com']);

    // 保存配置（修改）与发送测试邮件（新增）各记录一条，取后者的日志
    $log = OperationLog::query()->orderByDesc('id')->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($user->id);
    expect($log->username)->toBe($user->name);
    expect($log->module)->toBe('邮箱设置');
    expect($log->type)->toBe('新增');
    expect($log->summary)->toBe('邮箱设置 · 新增');
    expect($log->status)->toBeTrue();
    expect($log->url)->toContain('/admin/settings/mail/send-test');
    expect($log->ip)->not->toBe('');
    expect($log->cost_ms)->toBeGreaterThanOrEqual(0);

    // 发送测试邮件的参数被记录，且不含敏感关键词字段
    expect($log->parameters)->toBe(['test_email' => 'test@example.com']);
});

test('request parameters are recorded with sensitive keys removed', function () {
    Mail::fake();

    $user = User::factory()->superAdmin()->create();

    $this
        ->actingAs($user)
        ->patch(route('admin.mail.update'), [
            'enabled' => '1',
            'from_email' => 'no-reply@example.com',
            'from_name' => '站点机器人',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_username' => 'smtp-user',
            'smtp_password' => 'smtp-secret',
            'smtp_encryption' => 'tls',
        ]);

    $log = OperationLog::query()->where('type', '修改')->first();

    expect($log)->not->toBeNull();
    expect($log->parameters)->not->toBeNull();
    // 普通字段保留
    expect($log->parameters)->toHaveKey('smtp_host', 'smtp.example.com');
    expect($log->parameters)->toHaveKey('smtp_port', '587');
    // 含 password/secret/token 关键词的字段一律剔除
    expect($log->parameters)->not->toHaveKey('smtp_password');
    expect($log->parameters)->not->toHaveKey('_token');
});

test('sensitive keys are removed at any nesting level', function () {
    Mail::fake();

    $user = User::factory()->superAdmin()->create();

    // 携带嵌套结构的额外字段，验证任意层级的敏感键都会被剔除
    $this
        ->actingAs($user)
        ->patch(route('admin.mail.update'), [
            'enabled' => '1',
            'from_email' => 'no-reply@example.com',
            'from_name' => '站点机器人',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_username' => 'smtp-user',
            'smtp_encryption' => 'tls',
            'extra' => [
                'password' => 'plain-secret',
                'api_key' => 'ak-123',
                'safe' => '保留',
                'deep' => ['token' => 'tk-1', 'keep' => 1],
            ],
            'recovery_code' => 'rc-001',
            'otp' => '654321',
        ]);

    $log = OperationLog::query()->where('type', '修改')->first();

    expect($log)->not->toBeNull();
    expect($log->parameters)->not->toBeNull();

    // 顶层与嵌套的非敏感字段保留
    expect($log->parameters)->toHaveKey('smtp_host', 'smtp.example.com');
    expect($log->parameters['extra'])->toHaveKey('safe', '保留');
    expect($log->parameters['extra']['deep'])->toBe(['keep' => 1]);

    // 任意层级的敏感键（含 recovery_code / otp / code 类）一律剔除
    expect($log->parameters)->not->toHaveKey('recovery_code');
    expect($log->parameters)->not->toHaveKey('otp');
    expect($log->parameters['extra'])->not->toHaveKey('password');
    expect($log->parameters['extra'])->not->toHaveKey('api_key');
    expect($log->parameters['extra']['deep'])->not->toHaveKey('token');
});

test('operations without parameters record null parameters', function () {
    $user = User::factory()->superAdmin()->create();

    // 删除操作不携带请求参数
    $this
        ->actingAs($user)
        ->delete(route('admin.operation-logs.clear'));

    $log = OperationLog::query()->first();

    expect($log)->not->toBeNull();
    expect($log->type)->toBe('删除');
    expect($log->parameters)->toBeNull();
});

test('admin get requests are not recorded', function () {
    $user = User::factory()->superAdmin()->create();

    $this
        ->actingAs($user)
        ->get(route('admin.media.index'));

    expect(OperationLog::count())->toBe(0);
});

test('validation failures are recorded as failed operations', function () {
    $user = User::factory()->superAdmin()->create();

    // 发送测试邮件缺少参数触发验证失败（302 回退带 errors）
    $this
        ->actingAs($user)
        ->from(route('admin.mail.edit'))
        ->post(route('admin.mail.sendTest'), []);

    $log = OperationLog::query()->first();

    expect($log)->not->toBeNull();
    expect($log->status)->toBeFalse();
    expect($log->error_message)->not->toBeNull();
});

test('selected operation logs can be deleted', function () {
    $user = User::factory()->superAdmin()->create();
    $keep = OperationLog::factory()->create();
    $target = OperationLog::factory()->count(2)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('admin.operation-logs.destroy'), [
            'ids' => $target->pluck('id')->all(),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.operation-logs.index'));

    // 保留的记录仍在，目标记录被删除；中间件会额外记录本次删除操作
    expect(OperationLog::whereKey($keep->id)->exists())->toBeTrue();
    expect(OperationLog::whereIn('id', $target->pluck('id'))->count())->toBe(0);
    expect(OperationLog::where('type', '删除')->count())->toBe(1);
});

test('operation log deletion requires ids', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('admin.operation-logs.destroy'), []);

    $response->assertSessionHasErrors(['ids']);
});

test('operation logs can be cleared', function () {
    $user = User::factory()->superAdmin()->create();
    OperationLog::factory()->count(3)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('admin.operation-logs.clear'));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.operation-logs.index'));

    // 清空后中间件会记录本次清空操作，因此仅剩 1 条
    expect(OperationLog::count())->toBe(1);
    expect(OperationLog::first()->type)->toBe('删除');
});

test('operation logs can be exported as csv', function () {
    $user = User::factory()->superAdmin()->create();
    OperationLog::factory()->create(['username' => '张三', 'status' => true]);
    OperationLog::factory()->failed()->create(['username' => '李四']);

    $response = $this
        ->actingAs($user)
        ->get(route('admin.operation-logs.export'));

    $response->assertOk();

    $content = $response->streamedContent();

    // 响应头与 CSV 表头（fputcsv 会对含空格字段加引号）
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
    expect($content)->toContain('日志编号,操作结果,模块名称,操作摘要,操作类型,操作人员,请求地址,"IP 地址",请求参数,错误信息,耗时,操作时间');
    expect($content)->toContain('成功,媒体中心,"媒体中心 · 修改",修改,张三');
    expect($content)->toContain('失败,媒体中心,"媒体中心 · 修改",修改,李四');
});

test('operation log export respects filters', function () {
    $user = User::factory()->superAdmin()->create();
    OperationLog::factory()->create(['username' => '张三', 'status' => true]);
    OperationLog::factory()->failed()->create(['username' => '李四']);

    $response = $this
        ->actingAs($user)
        ->get(route('admin.operation-logs.export', ['status' => 'fail']));

    $response->assertOk();

    $content = $response->streamedContent();

    expect($content)->toContain('失败,媒体中心,"媒体中心 · 修改",修改,李四');
    expect($content)->not->toContain('成功,媒体中心');
});

test('operation log list filters by search keyword', function () {
    $user = User::factory()->superAdmin()->create();
    OperationLog::factory()->create(['username' => '张三']);
    OperationLog::factory()->create(['username' => '李四']);

    $response = $this
        ->actingAs($user)
        ->get(route('admin.operation-logs.index', ['search' => '张三']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('admin/logs/operation/Index')
        ->where('logs.total', 1)
        ->where('logs.data.0.username', '张三')
    );
});
