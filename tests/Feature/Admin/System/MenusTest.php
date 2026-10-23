<?php

use App\Models\Menu;
use App\Models\User;
use Database\Seeders\MenusSeeder;
use Database\Seeders\RolesSeeder;
use Inertia\Testing\AssertableInertia;

/**
 * 菜单管理功能测试：菜单树列表、CRUD、校验规则（类型/路径/图标/权限点/父级/防环）、
 * 删除保护与侧边栏共享数据过滤
 */
beforeEach(function () {
    // 写入注册表权限点与内置角色，菜单权限点过滤依赖角色
    $this->seed(RolesSeeder::class);
    // 菜单管理受 menus.* 权限保护，统一使用超级管理员角色放行
    $this->admin = User::factory()->superAdmin()->create();
});

test('guest cannot access menu management', function () {
    $this->get(route('admin.menus.index'))
        ->assertRedirect(route('login'));
});

test('menu management page is displayed', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.menus.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/menus/Index')
            ->has('tree')
            ->has('types')
            ->has('parents')
            ->has('groups')
            ->has('icons'));
});

test('menu can be created', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.menus.store'), [
            'type' => 'menu',
            'title' => '报表中心',
            'path' => '/admin/reports',
            'sort' => 9,
        ])
        ->assertRedirect(route('admin.menus.index'));

    expect(Menu::query()->where('title', '报表中心')->where('path', '/admin/reports')->exists())->toBeTrue();
});

test('directory can be created with nested child', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.menus.store'), [
            'type' => 'directory',
            'title' => '内容管理',
        ])
        ->assertRedirect(route('admin.menus.index'));

    $directory = Menu::query()->where('title', '内容管理')->firstOrFail();

    $this->actingAs($this->admin)
        ->post(route('admin.menus.store'), [
            'type' => 'menu',
            'title' => '文章列表',
            'path' => '/admin/articles',
            'parent_id' => $directory->id,
        ])
        ->assertRedirect(route('admin.menus.index'));

    expect($directory->fresh()->hasChildren())->toBeTrue();
});

test('menu type must be a valid enum value', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.menus.index'))
        ->post(route('admin.menus.store'), [
            'type' => 'widget',
            'title' => '非法类型',
        ])
        ->assertSessionHasErrors('type');
});

test('path is required for menu type and prohibited for other types', function () {
    // menu 类型缺 path
    $this->actingAs($this->admin)
        ->from(route('admin.menus.index'))
        ->post(route('admin.menus.store'), [
            'type' => 'menu',
            'title' => '缺少地址',
        ])
        ->assertSessionHasErrors('path');

    // title 类型携带 path
    $this->actingAs($this->admin)
        ->from(route('admin.menus.index'))
        ->post(route('admin.menus.store'), [
            'type' => 'title',
            'title' => '分组标题',
            'path' => '/admin/somewhere',
        ])
        ->assertSessionHasErrors('path');
});

test('path must start with slash, external links are rejected', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.menus.index'))
        ->post(route('admin.menus.store'), [
            'type' => 'menu',
            'title' => '相对地址',
            'path' => 'admin/reports',
        ])
        ->assertSessionHasErrors('path');

    $this->actingAs($this->admin)
        ->from(route('admin.menus.index'))
        ->post(route('admin.menus.store'), [
            'type' => 'menu',
            'title' => '外链菜单',
            'path' => 'https://example.com/docs',
        ])
        ->assertSessionHasErrors('path');

    expect(Menu::query()->where('title', '外链菜单')->doesntExist())->toBeTrue();
});

test('icon and permission must come from the whitelist', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.menus.index'))
        ->post(route('admin.menus.store'), [
            'type' => 'menu',
            'title' => '非法图标',
            'path' => '/admin/a',
            'icon' => 'NotAnIcon',
        ])
        ->assertSessionHasErrors('icon');

    $this->actingAs($this->admin)
        ->from(route('admin.menus.index'))
        ->post(route('admin.menus.store'), [
            'type' => 'menu',
            'title' => '非法权限点',
            'path' => '/admin/b',
            'permission' => 'foo.bar',
        ])
        ->assertSessionHasErrors('permission');
});

test('parent must be a directory or menu type, and menu parent only accepts button child', function () {
    // 挂在普通菜单下的非按钮子项被拒绝
    $menu = Menu::query()->create([
        'type' => 'menu',
        'title' => '普通菜单',
        'path' => '/admin/media',
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.menus.index'))
        ->post(route('admin.menus.store'), [
            'type' => 'menu',
            'title' => '挂错父级',
            'path' => '/admin/other',
            'parent_id' => $menu->id,
        ])
        ->assertSessionHasErrors('parent_id');

    // 按钮可以挂在菜单下作为页面内权限标识
    $this->actingAs($this->admin)
        ->post(route('admin.menus.store'), [
            'type' => 'button',
            'title' => '上传媒体',
            'permission' => 'media.upload',
            'parent_id' => $menu->id,
        ])
        ->assertRedirect(route('admin.menus.index'));

    expect($menu->fresh()->hasChildren())->toBeTrue();
});

test('cannot set parent to own descendant to prevent cycles', function () {
    $parent = Menu::query()->create(['type' => 'directory', 'title' => '目录A']);
    $child = Menu::query()->create(['type' => 'directory', 'title' => '目录B', 'parent_id' => $parent->id]);

    // 把目录A挂到其子级目录B下，形成环
    $this->actingAs($this->admin)
        ->from(route('admin.menus.index'))
        ->patch(route('admin.menus.update', ['menu' => $parent->id]), [
            'type' => 'directory',
            'title' => '目录A',
            'parent_id' => $child->id,
        ])
        ->assertSessionHasErrors('parent_id');

    // 把目录A挂到自己名下同样被拒绝
    $this->actingAs($this->admin)
        ->from(route('admin.menus.index'))
        ->patch(route('admin.menus.update', ['menu' => $parent->id]), [
            'type' => 'directory',
            'title' => '目录A',
            'parent_id' => $parent->id,
        ])
        ->assertSessionHasErrors('parent_id');
});

test('menu can be updated', function () {
    $menu = Menu::query()->create([
        'type' => 'menu',
        'title' => '旧名称',
        'path' => '/admin/old',
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.menus.update', ['menu' => $menu->id]), [
            'type' => 'menu',
            'title' => '新名称',
            'path' => '/admin/new',
            'icon' => 'ListTree',
            'permission' => 'menus.view',
        ])
        ->assertRedirect(route('admin.menus.index'));

    expect($menu->fresh())
        ->title->toBe('新名称')
        ->path->toBe('/admin/new')
        ->icon->toBe('ListTree')
        ->permission->toBe('menus.view');
});

test('menu with children cannot be deleted until children are removed', function () {
    $parent = Menu::query()->create(['type' => 'directory', 'title' => '父目录']);
    $child = Menu::query()->create(['type' => 'menu', 'title' => '子菜单', 'path' => '/admin/x', 'parent_id' => $parent->id]);

    $this->actingAs($this->admin)
        ->from(route('admin.menus.index'))
        ->delete(route('admin.menus.destroy', ['menu' => $parent->id]))
        ->assertSessionHasErrors('menu');

    expect($parent->fresh())->not->toBeNull();

    // 先删除子菜单，再删除父目录
    $this->actingAs($this->admin)
        ->delete(route('admin.menus.destroy', ['menu' => $child->id]))
        ->assertRedirect(route('admin.menus.index'));

    $this->actingAs($this->admin)
        ->delete(route('admin.menus.destroy', ['menu' => $parent->id]))
        ->assertRedirect(route('admin.menus.index'));

    expect(Menu::query()->whereKey([$parent->id, $child->id])->count())->toBe(0);
});

test('sidebar excludes buttons, invisible menus, unauthorized menus and empty directories', function () {
    // 目录：含一个需 media.view 权限的菜单与一个按钮
    $directory = Menu::query()->create(['type' => 'directory', 'title' => '内容管理', 'sort' => 1]);
    Menu::query()->create(['type' => 'menu', 'title' => '媒体中心', 'path' => '/admin/media', 'sort' => 1, 'parent_id' => $directory->id, 'permission' => 'media.view']);
    Menu::query()->create(['type' => 'button', 'title' => '导出按钮', 'sort' => 2, 'parent_id' => $directory->id]);
    // 顶级可见菜单：无需权限
    Menu::query()->create(['type' => 'menu', 'title' => '公开菜单', 'path' => '/admin/open', 'sort' => 2]);
    // 不可见菜单
    Menu::query()->create(['type' => 'menu', 'title' => '隐藏菜单', 'path' => '/admin/hidden', 'sort' => 3, 'is_visible' => false]);
    // 空目录
    Menu::query()->create(['type' => 'directory', 'title' => '空目录', 'sort' => 4]);

    // 超管：可见全部（按钮、不可见、空目录除外）
    $this->actingAs($this->admin)
        ->get(route('admin.menus.index'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $page->component('admin/menus/Index')
                ->has('menus', 2)
                ->where('menus.0.title', '内容管理')
                ->has('menus.0.children', 1)
                ->where('menus.0.children.0.title', '媒体中心')
                ->missing('menus.0.children.1')
                ->where('menus.1.title', '公开菜单');
        });

    // 仅授予 menus.view 的用户：媒体中心被过滤后目录为空目录剔除，仅剩公开菜单
    $plainUser = User::factory()->create();
    $plainUser->givePermissionTo('menus.view');
    $this->actingAs($plainUser)
        ->get(route('admin.menus.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('menus', 1)
            ->where('menus.0.title', '公开菜单'));
});

test('menus seeder is idempotent', function () {
    $this->seed(MenusSeeder::class);
    $countAfterFirst = Menu::query()->count();

    $this->seed(MenusSeeder::class);

    expect(Menu::query()->count())->toBe($countAfterFirst);
    expect($countAfterFirst)->toBe(27);
});

test('menus seeder attaches button permission marks under every menu', function () {
    $this->seed(MenusSeeder::class);

    // 媒体中心：上传/编辑/删除
    $media = Menu::query()->where('title', '媒体中心')->whereNull('parent_id')->firstOrFail();
    expect(
        Menu::query()->where('parent_id', $media->id)->where('type', 'button')->orderBy('sort')->pluck('permission')->all()
    )->toBe(['media.upload', 'media.update', 'media.delete']);

    // 登录日志：导出/删除与清空
    $loginLog = Menu::query()->where('title', '登录日志')->firstOrFail();
    expect(
        Menu::query()->where('parent_id', $loginLog->id)->where('type', 'button')->orderBy('sort')->pluck('permission')->all()
    )->toBe(['logs.export', 'logs.clear']);

    // 用户管理：新增/编辑/拉黑/管理用户角色
    $users = Menu::query()->where('title', '用户管理')->firstOrFail();
    expect(
        Menu::query()->where('parent_id', $users->id)->where('type', 'button')->orderBy('sort')->pluck('permission')->all()
    )->toBe(['users.create', 'users.update', 'users.block', 'users.manage']);
});

test('menus seeder builds directory hierarchy and re-parents legacy flat rows', function () {
    // 模拟旧版平铺数据：登录日志为顶级菜单
    Menu::query()->create([
        'type' => 'menu',
        'title' => '登录日志',
        'path' => '/admin/logs/login',
        'sort' => 3,
    ]);

    $this->seed(MenusSeeder::class);

    $directory = Menu::query()->where('title', '日志管理')->where('type', 'directory')->firstOrFail();
    expect($directory->hasChildren())->toBeTrue();

    // 旧平铺行被挂到目录下而非重复创建
    $loginLog = Menu::query()->where('title', '登录日志')->firstOrFail();
    expect($loginLog->parent_id)->toBe($directory->id);
    expect(Menu::query()->where('title', '登录日志')->count())->toBe(1);
});
