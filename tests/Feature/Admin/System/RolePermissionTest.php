<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Inertia\Testing\AssertableInertia;

/**
 * 角色管理功能测试：角色列表、创建、编辑、删除、权限矩阵同步与内置角色保护
 */
beforeEach(function () {
    // 写入注册表权限点与内置超级管理员角色
    $this->seed(RolesSeeder::class);
    // 管理操作受 roles.* 权限保护，统一使用超级管理员角色放行
    $this->admin = User::factory()->superAdmin()->create();
});

test('role list page is displayed', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/roles/Index')
            ->has('roles')
            ->has('groups'));
});

test('guest cannot access role list', function () {
    $this->get(route('admin.roles.index'))
        ->assertRedirect(route('login'));
});

test('roles can be created', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.roles.store'), ['title' => '审核员', 'name' => 'reviewer'])
        ->assertRedirect(route('admin.roles.index'));

    $role = Role::query()->where('name', 'reviewer')->firstOrFail();

    expect($role->title)->toBe('审核员')
        ->and($role->is_builtin)->toBeFalse();
});

test('role name must be a code-like identifier', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->post(route('admin.roles.store'), ['title' => '审核员', 'name' => '审核员'])
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHasErrors('name');

    expect(Role::query()->where('title', '审核员')->exists())->toBeFalse();
});

test('duplicate role name is rejected', function () {
    Role::query()->create(['name' => 'reviewer', 'title' => '审核员', 'guard_name' => 'web']);

    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->post(route('admin.roles.store'), ['title' => '审核员', 'name' => 'reviewer'])
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHasErrors('name');
});

test('builtin role cannot be renamed or deleted', function () {
    $role = Role::query()->where('name', 'super-admin')->firstOrFail();

    $this->actingAs($this->admin)
        ->patch(route('admin.roles.update', ['role' => $role->id]), ['title' => '改名'])
        ->assertSessionHasErrors('title');

    $this->actingAs($this->admin)
        ->delete(route('admin.roles.destroy', ['role' => $role->id]))
        ->assertSessionHasErrors('title');

    expect($role->fresh()->title)->toBe('超级管理员')
        ->and($role->fresh()->name)->toBe('super-admin');
});

test('non-builtin role can be renamed and deleted', function () {
    $role = Role::query()->create(['name' => 'temp-role', 'title' => '临时角色', 'guard_name' => 'web']);

    // 编辑仅修改显示名称，标识不可变更
    $this->actingAs($this->admin)
        ->patch(route('admin.roles.update', ['role' => $role->id]), ['title' => '临时角色二'])
        ->assertRedirect(route('admin.roles.index'));

    expect($role->fresh()->title)->toBe('临时角色二')
        ->and($role->fresh()->name)->toBe('temp-role');

    $this->actingAs($this->admin)
        ->delete(route('admin.roles.destroy', ['role' => $role->id]))
        ->assertRedirect(route('admin.roles.index'));

    expect($role->fresh())->toBeNull();
});

test('role permissions can be synced from matrix', function () {
    $role = Role::query()->create(['name' => '内容员', 'guard_name' => 'web']);

    $this->actingAs($this->admin)
        ->put(route('admin.roles.permissions', ['role' => $role->id]), [
            'permissions' => ['media.view', 'media.upload', 'logs.view'],
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['logs.view', 'media.upload', 'media.view']);
});

test('unknown permission is rejected when syncing matrix', function () {
    $role = Role::query()->create(['name' => '内容员', 'guard_name' => 'web']);

    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->put(route('admin.roles.permissions', ['role' => $role->id]), [
            'permissions' => ['not.exists'],
        ])
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHasErrors('permissions.*');
});

test('user with roles.view permission can access role list', function () {
    $user = User::factory()->create();
    $role = Role::query()->create(['name' => 'viewer', 'title' => '只读', 'guard_name' => 'web']);
    $role->givePermissionTo('roles.view');
    $user->assignRole($role);

    $this->actingAs($user)
        ->get(route('admin.roles.index'))
        ->assertOk();
});

test('user without roles.create permission cannot create role', function () {
    $user = User::factory()->create();
    $role = Role::query()->create(['name' => 'viewer', 'title' => '只读', 'guard_name' => 'web']);
    $role->givePermissionTo('roles.view');
    $user->assignRole($role);

    $this->actingAs($user)
        ->post(route('admin.roles.store'), ['title' => '越权角色', 'name' => 'overrule'])
        ->assertForbidden();

    expect(Role::query()->where('name', 'overrule')->exists())->toBeFalse();
});
