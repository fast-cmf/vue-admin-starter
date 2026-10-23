<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Inertia\Testing\AssertableInertia;

/**
 * 用户新增/编辑/拉黑功能测试：创建与编辑校验、拉黑登录拦截、超管与自身保护
 */
beforeEach(function () {
    // 写入注册表权限点与内置超级管理员角色
    $this->seed(RolesSeeder::class);
    // 管理操作受 users.* 权限保护，统一使用超级管理员角色放行
    $this->admin = User::factory()->superAdmin()->create();
});

test('admin can create a user', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.store'), [
            'name' => '张三',
            'email' => 'zhangsan@example.com',
            'password' => 'Password!123456',
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'zhangsan@example.com')->firstOrFail();

    expect($user->name)->toBe('张三')
        ->and($user->blocked_at)->toBeNull();
});

test('duplicate email is rejected when creating a user', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.users.index'))
        ->post(route('admin.users.store'), [
            'name' => '重复邮箱',
            'email' => $this->admin->email,
            'password' => 'Password!123456',
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('email');
});

test('admin can update user name and email', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.users.update', ['user' => $user->id]), [
            'name' => '新名字',
            'email' => 'new@example.com',
            'password' => '',
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($user->fresh()->name)->toBe('新名字')
        ->and($user->fresh()->email)->toBe('new@example.com');
});

test('password is only updated when provided', function () {
    $user = User::factory()->create();
    $oldHash = $user->password;

    $this->actingAs($this->admin)
        ->put(route('admin.users.update', ['user' => $user->id]), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($user->fresh()->password)->toBe($oldHash);

    $this->actingAs($this->admin)
        ->put(route('admin.users.update', ['user' => $user->id]), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'NewPassword!123456',
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($user->fresh()->password)->not->toBe($oldHash);
});

test('admin can block a user', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.users.toggleBlock', ['user' => $user->id]))
        ->assertRedirect(route('admin.users.index'));

    expect($user->fresh()->blocked_at)->not->toBeNull();
});

test('admin can unblock a user', function () {
    $user = User::factory()->create();
    $user->forceFill(['blocked_at' => now()])->save();

    $this->actingAs($this->admin)
        ->patch(route('admin.users.toggleBlock', ['user' => $user->id]))
        ->assertRedirect(route('admin.users.index'));

    expect($user->fresh()->blocked_at)->toBeNull();
});

test('blocked user cannot login', function () {
    $user = User::factory()->create([
        'password' => 'Password!123456',
        'blocked_at' => now(),
    ]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password!123456',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('unblocked user can login', function () {
    $user = User::factory()->create([
        'password' => 'Password!123456',
    ]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password!123456',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

test('cannot block yourself', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.users.index'))
        ->patch(route('admin.users.toggleBlock', ['user' => $this->admin->id]))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('blocked_at');

    expect($this->admin->fresh()->blocked_at)->toBeNull();
});

test('super-admin cannot be blocked', function () {
    $anotherSuperAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($this->admin)
        ->from(route('admin.users.index'))
        ->patch(route('admin.users.toggleBlock', ['user' => $anotherSuperAdmin->id]))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('blocked_at');

    expect($anotherSuperAdmin->fresh()->blocked_at)->toBeNull();
});

test('blocked user session is terminated on next request', function () {
    $user = User::factory()->create();
    $user->forceFill(['blocked_at' => now()])->save();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('user without users.create permission cannot create user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.users.store'), [
            'name' => '越权用户',
            'email' => 'over@example.com',
            'password' => 'Password!123456',
        ])
        ->assertForbidden();

    expect(User::query()->where('email', 'over@example.com')->exists())->toBeFalse();
});

test('user list exposes blocked_at in payload', function () {
    $blocked = User::factory()->create();
    $blocked->forceFill(['blocked_at' => now()])->save();

    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/users/Index')
            ->where('users.data.1.blocked_at', fn ($value) => $value !== null));
});
