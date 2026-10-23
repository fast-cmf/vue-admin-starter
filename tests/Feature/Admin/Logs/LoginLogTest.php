<?php

use App\Models\LoginLog;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * 模拟 Chrome / Windows 10 的 User-Agent
 */
const TEST_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36';

test('login log list page is displayed', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('admin.login-logs.index'));

    $response->assertOk();
});

test('guest cannot access login log list', function () {
    $response = $this->get(route('admin.login-logs.index'));

    $response->assertRedirect(route('login'));
});

test('successful login is recorded in login logs', function () {
    $user = User::factory()->superAdmin()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response = $this
        ->withHeaders(['User-Agent' => TEST_UA])
        ->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

    $response->assertRedirect();

    $log = LoginLog::query()->where('account', 'admin@example.com')->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($user->id);
    expect($log->status)->toBeTrue();
    expect($log->message)->toBe('登录成功');
    expect($log->browser)->toBe('Chrome 155.0.0.0');
    expect($log->platform)->toBe('Windows 10');
    expect($log->ip)->not->toBe('');
});

test('failed login is recorded in login logs', function () {
    $user = User::factory()->superAdmin()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this
        ->withHeaders(['User-Agent' => TEST_UA])
        ->from(route('login'))
        ->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

    $log = LoginLog::query()->where('account', 'admin@example.com')->first();

    expect($log)->not->toBeNull();
    // 账号存在但密码错误：事件会携带该用户，日志仍关联 user_id
    expect($log->user_id)->toBe($user->id);
    expect($log->status)->toBeFalse();
    expect($log->message)->toBe('账号或密码错误');
});

test('login logs with unknown account are recorded', function () {
    $this
        ->post(route('login'), [
            'email' => 'ghost@example.com',
            'password' => 'whatever',
        ]);

    $log = LoginLog::query()->where('account', 'ghost@example.com')->first();

    expect($log)->not->toBeNull();
    expect($log->status)->toBeFalse();
});

test('selected login logs can be deleted', function () {
    $user = User::factory()->superAdmin()->create();
    $keep = LoginLog::factory()->create();
    $target = LoginLog::factory()->count(2)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('admin.login-logs.destroy'), [
            'ids' => $target->pluck('id')->all(),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.login-logs.index'));

    expect(LoginLog::count())->toBe(1);
    expect(LoginLog::first()->id)->toBe($keep->id);
});

test('login log deletion requires ids', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('admin.login-logs.destroy'), []);

    $response->assertSessionHasErrors(['ids']);
});

test('login logs can be cleared', function () {
    $user = User::factory()->superAdmin()->create();
    LoginLog::factory()->count(3)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('admin.login-logs.clear'));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.login-logs.index'));

    expect(LoginLog::count())->toBe(0);
});

test('login logs can be exported as csv', function () {
    $user = User::factory()->superAdmin()->create();
    LoginLog::factory()->create(['account' => 'admin@example.com', 'status' => true]);
    LoginLog::factory()->failed()->create(['account' => 'ghost@example.com']);

    $response = $this
        ->actingAs($user)
        ->get(route('admin.login-logs.export'));

    $response->assertOk();

    $content = $response->streamedContent();

    // 响应头与 CSV 表头（fputcsv 会对含空格字段加引号）
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
    expect($content)->toContain('用户账号,登录状态,"IP 地址",浏览器,操作系统,提示信息,登录时间');
    expect($content)->toContain('admin@example.com,成功');
    expect($content)->toContain('ghost@example.com,失败');
});

test('login log export respects filters', function () {
    $user = User::factory()->superAdmin()->create();
    LoginLog::factory()->create(['account' => 'admin@example.com', 'status' => true]);
    LoginLog::factory()->failed()->create(['account' => 'ghost@example.com']);

    $response = $this
        ->actingAs($user)
        ->get(route('admin.login-logs.export', ['status' => 'fail']));

    $response->assertOk();

    $content = $response->streamedContent();

    expect($content)->toContain('ghost@example.com,失败');
    expect($content)->not->toContain('admin@example.com,成功');
});

test('login log list filters by search keyword', function () {
    $user = User::factory()->superAdmin()->create();
    LoginLog::factory()->create(['account' => 'admin@example.com']);
    LoginLog::factory()->create(['account' => 'ghost@example.com']);

    $response = $this
        ->actingAs($user)
        ->get(route('admin.login-logs.index', ['search' => 'admin']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('admin/logs/login/Index')
        ->where('logs.total', 1)
        ->where('logs.data.0.account', 'admin@example.com')
    );
});
