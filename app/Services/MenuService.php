<?php

namespace App\Services;

use App\Enums\MenuType;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * 菜单服务：构建菜单树、构建侧边栏数据（按用户权限过滤）
 */
class MenuService
{
    /**
     * 构建侧边栏菜单树：仅下发目录/菜单/标题类型，剔除不可见与无权限节点，
     * 空目录与空标题不渲染；按钮类型仅作页面内权限标识管理，不下发
     *
     * @return list<array{id: int, type: string, title: string, path: string|null, icon: string|null, children: list<mixed>}>
     */
    public function sidebarFor(?User $user): array
    {
        $menus = Menu::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return $this->buildSidebarTree($menus, null, $user);
    }

    /**
     * 递归构建并过滤侧边栏树
     *
     * @param  Collection<int, Menu>  $menus
     * @return list<mixed>
     */
    protected function buildSidebarTree(Collection $menus, ?int $parentId, ?User $user): array
    {
        $nodes = [];

        foreach ($menus as $menu) {
            if ($menu->parent_id !== $parentId) {
                continue;
            }

            // 按钮类型不下发；不可见节点剔除；绑定权限点但用户无权限的剔除
            if (! in_array($menu->type, MenuType::sidebarTypes(), true)) {
                continue;
            }

            if (! $menu->is_visible) {
                continue;
            }

            if ($menu->permission !== null && ! $this->userCan($user, $menu->permission)) {
                continue;
            }

            $children = $this->buildSidebarTree($menus, $menu->id, $user);

            // 目录/标题没有可见子节点时不渲染
            if (in_array($menu->type, [MenuType::Directory, MenuType::Title], true) && $children === []) {
                continue;
            }

            $nodes[] = [
                'id' => $menu->id,
                'type' => $menu->type->value,
                'title' => $menu->title,
                'path' => $menu->path,
                'icon' => $menu->icon,
                'children' => $children,
            ];
        }

        return $nodes;
    }

    /**
     * 判断用户是否拥有权限点（Gate::before 已放行超管）
     */
    protected function userCan(?User $user, string $permission): bool
    {
        return $user !== null && $user->can($permission);
    }
}
