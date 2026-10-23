<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Inertia\Testing\AssertableInertia;

/**
 * 用户管理功能测试：用户列表、角色分配与最后一个超级管理员保护
 */
beforeEach(function () {
    // 写入注册表权限点与内置超级管理员角色
    $this->seed(RolesSeeder::class);
    // 管理操作受 users.* 权限保护，统一使用超级管理员角色放行
    $this->admin = User::factory()->superAdmin()->create();
});

/**
 * 在后台创建一个自定义角色，替代已移除的内置 editor/viewer 供用例使用
 */
function createCustomRole(string $name, string $title, array $permissions = []): Role
{
    $role = Role::query()->create(['name' => $name, 'title' => $title, 'guard_name' => 'web']);
    $role->syncPermissions($permissions);

    return $role;
}

test('user list page is displayed', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/users/Index')
            ->has('users')
            ->has('roles'));
});

test('guest cannot access user list', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

test('user list supports keyword search', function () {
    User::factory()->create(['name' => '张小三']);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index', ['search' => '张小三']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.name', '张小三'));
});

test('roles can be assigned to user', function () {
    $user = User::factory()->create();
    $editor = createCustomRole('editor', '运营编辑');
    $viewer = createCustomRole('viewer', '只读');

    $this->actingAs($this->admin)
        ->put(route('admin.users.assignRoles', ['user' => $user->id]), [
            'roles' => [$editor->id, $viewer->id],
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($user->fresh()->roles->pluck('name')->sort()->values()->all())
        ->toBe(['editor', 'viewer']);
});

test('assigning unknown role id is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->from(route('admin.users.index'))
        ->put(route('admin.users.assignRoles', ['user' => $user->id]), [
            'roles' => [99999],
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('roles.*');
});

test('super-admin can be demoted when another super-admin remains', function () {
    $user = User::factory()->superAdmin()->create();
    $editor = createCustomRole('editor', '运营编辑');

    // 系统中已有两名超管（this->admin 与 $user），降级其中一人是允许的
    $this->actingAs($this->admin)
        ->put(route('admin.users.assignRoles', ['user' => $user->id]), [
            'roles' => [$editor->id],
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->hasRole('super-admin'))->toBeFalse();
});

test('last super-admin cannot be demoted', function () {
    // 移除 beforeEach 里的第二个超管角色，让操作目标成为系统中唯一的超管
    $this->admin->removeRole('super-admin');
    $this->admin->givePermissionTo('users.manage');

    $user = User::factory()->superAdmin()->create();
    $editor = createCustomRole('editor', '运营编辑');

    $this->actingAs($this->admin)
        ->from(route('admin.users.index'))
        ->put(route('admin.users.assignRoles', ['user' => $user->id]), [
            'roles' => [$editor->id],
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('roles');

    expect($user->fresh()->hasRole('super-admin'))->toBeTrue();
});

test('shared auth.can payload drives frontend button visibility', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.view');

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.can', fn ($can): bool => $can->contains('users.view') && ! $can->contains('users.create')));
});

test('user without users.view permission cannot access user list', function () {
    $user = User::factory()->create();
    $user->assignRole(createCustomRole('editor', '运营编辑'));

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('user with users.view permission can access user list', function () {
    $user = User::factory()->create();
    $user->assignRole(createCustomRole('viewer', '只读', ['users.view']));

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk();
});
