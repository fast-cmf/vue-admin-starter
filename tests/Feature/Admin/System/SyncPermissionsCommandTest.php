<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

/**
 * permission:sync 命令测试：注册表同步、既有权限保留与漂移检查
 */
test('sync command upserts registry permissions', function () {
    $this->artisan('permission:sync')->assertSuccessful();

    $registry = array_keys((array) config('permissions'));

    expect(Permission::query()->whereIn('name', $registry)->count())
        ->toBe(count($registry));
});

test('sync command keeps permissions removed from registry', function () {
    // 模拟历史遗留权限：注册表中已不存在，但库里仍有角色挂载
    $legacy = Permission::query()->create(['name' => 'legacy.action', 'guard_name' => 'web']);
    $role = Role::query()->create(['name' => '历史角色', 'guard_name' => 'web']);
    $role->givePermissionTo($legacy);

    $this->artisan('permission:sync')->assertSuccessful();

    expect($legacy->fresh())->not->toBeNull()
        ->and($role->fresh()->hasPermissionTo('legacy.action'))->toBeTrue();
});

test('sync command is idempotent', function () {
    $this->artisan('permission:sync')->assertSuccessful();
    $count = Permission::query()->count();

    $this->artisan('permission:sync')->assertSuccessful();

    expect(Permission::query()->count())->toBe($count);
});

test('sync command reports drift for unknown route permissions', function () {
    // 在路由上临时挂载一个注册表中不存在的权限点，验证漂移检查能发现
    Route::get('/_test-drift', fn () => 'ok')
        ->middleware('permission:not.registered')
        ->name('testing.drift');

    $this->artisan('permission:sync')
        ->expectsOutputToContain('not.registered')
        ->assertSuccessful();

    // 清理测试路由（重启应用即可，无需显式处理）
});

test('builtin roles are seeded with permissions', function () {
    $this->seed(RolesSeeder::class);

    $superAdmin = Role::query()->where('name', 'super-admin')->firstOrFail();

    expect(Role::query()->where('is_builtin', true)->count())->toBe(1)
        ->and($superAdmin->title)->toBe('超级管理员')
        ->and($superAdmin->hasPermissionTo('media.upload'))->toBeTrue()
        ->and($superAdmin->hasPermissionTo('roles.create'))->toBeTrue();
});
