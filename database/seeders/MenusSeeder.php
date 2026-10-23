<?php

namespace Database\Seeders;

use App\Enums\MenuType;
use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * 初始菜单 Seeder：按「目录 + 菜单 + 按钮」层级写入 menus 表（幂等，自动归位历史平铺数据）
 *
 * 约定：每个菜单下挂该页面的操作权限点作为「按钮」类型子项（页面内权限标识，不进侧边栏），
 * 之后新增菜单时同样先登记权限点，再在此处或菜单管理页为其添加按钮子项
 */
class MenusSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 历史更名归位：旧名「角色权限」统一改为「角色管理」，避免按 title 幂等写入时产生重复行
        Menu::query()->where('title', '角色权限')->update(['title' => '角色管理']);

        // 顶级菜单：[title, path, icon, permission, sort, [[按钮 title, permission, sort], ...]]
        // path 存站内相对路径（/ 开头），不写全 URL，避免域名/环境变化导致失效
        $topItems = [
            ['仪表盘', '/admin/dashboard', 'LayoutGrid', 'dashboard.view', 1, []],
            ['媒体中心', '/admin/media', 'Images', 'media.view', 2, [
                ['上传媒体', 'media.upload', 1],
                ['编辑媒体', 'media.update', 2],
                ['删除媒体', 'media.delete', 3],
            ]],
        ];

        foreach ($topItems as [$title, $path, $icon, $permission, $sort, $buttons]) {
            $this->upsertMenuWithButtons($title, $path, $icon, $permission, $sort, $buttons);
        }

        // 目录及其子菜单：[目录名, 目录图标, 目录排序, [[子菜单 title, path, icon, permission, sort, buttons], ...]]
        $groups = [
            ['日志管理', 'ScrollText', 3, [
                ['登录日志', '/admin/logs/login', 'ScrollText', 'logs.view', 1, [
                    ['导出日志', 'logs.export', 1],
                    ['删除与清空日志', 'logs.clear', 2],
                ]],
                ['操作日志', '/admin/logs/operation', 'ClipboardList', 'logs.view', 2, [
                    ['导出日志', 'logs.export', 1],
                    ['删除与清空日志', 'logs.clear', 2],
                ]],
            ]],
            ['系统管理', 'Settings', 4, [
                ['用户管理', '/admin/users', 'UsersRound', 'users.view', 1, [
                    ['新增用户', 'users.create', 1],
                    ['编辑用户', 'users.update', 2],
                    ['拉黑用户', 'users.block', 3],
                    ['管理用户角色', 'users.manage', 4],
                ]],
                ['角色管理', '/admin/roles', 'ShieldCheck', 'roles.view', 2, [
                    ['新增角色', 'roles.create', 1],
                    ['权限配置', 'roles.permissions', 2],
                    ['编辑角色', 'roles.update', 3],
                    ['删除角色', 'roles.delete', 4],
                ]],
                ['菜单管理', '/admin/menus', 'ListTree', 'menus.view', 3, [
                    ['新增菜单', 'menus.create', 1],
                    ['编辑菜单', 'menus.update', 2],
                    ['删除菜单', 'menus.delete', 3],
                ]],
            ]],
        ];

        foreach ($groups as [$directoryTitle, $directoryIcon, $directorySort, $children]) {
            $directory = Menu::query()->updateOrCreate(
                ['title' => $directoryTitle, 'parent_id' => null],
                [
                    'type' => MenuType::Directory,
                    'icon' => $directoryIcon,
                    'sort' => $directorySort,
                    'is_visible' => true,
                ],
            );

            foreach ($children as [$title, $path, $icon, $permission, $sort, $buttons]) {
                // 历史数据归位：旧版 Seeder 建的同名平铺菜单（parent_id=null）挂到目录下，避免重复
                Menu::query()
                    ->where('title', $title)
                    ->whereNull('parent_id')
                    ->whereKeyNot($directory->id)
                    ->update(['parent_id' => $directory->id]);

                $this->upsertMenuWithButtons($title, $path, $icon, $permission, $sort, $buttons, $directory->id);
            }
        }
    }

    /**
     * 写入单个菜单并挂载其按钮权限标识子项
     *
     * @param  list<array{0: string, 1: string, 2: int}>  $buttons
     */
    protected function upsertMenuWithButtons(
        string $title,
        ?string $path,
        ?string $icon,
        ?string $permission,
        int $sort,
        array $buttons,
        ?int $parentId = null,
    ): void {
        $menu = Menu::query()->updateOrCreate(
            ['title' => $title, 'parent_id' => $parentId],
            [
                'type' => MenuType::Menu,
                'path' => $path,
                'icon' => $icon,
                'sort' => $sort,
                'is_visible' => true,
                'permission' => $permission,
            ],
        );

        foreach ($buttons as [$buttonTitle, $buttonPermission, $buttonSort]) {
            Menu::query()->updateOrCreate(
                ['title' => $buttonTitle, 'parent_id' => $menu->id],
                [
                    'type' => MenuType::Button,
                    'sort' => $buttonSort,
                    'is_visible' => true,
                    'permission' => $buttonPermission,
                ],
            );
        }
    }
}
