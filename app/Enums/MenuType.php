<?php

namespace App\Enums;

/**
 * 菜单类型：目录（可折叠父级）、菜单（可点击跳转）、标题（分组文字标签）、按钮（页面内操作权限标识，不进侧边栏）
 */
enum MenuType: string
{
    case Directory = 'directory';
    case Menu = 'menu';
    case Title = 'title';
    case Button = 'button';

    /**
     * 中文标签
     */
    public function label(): string
    {
        return match ($this) {
            self::Directory => '目录',
            self::Menu => '菜单',
            self::Title => '标题',
            self::Button => '按钮',
        };
    }

    /**
     * 全部类型的 [value => 中文标签] 映射，供前端下拉使用
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    /**
     * 侧边栏渲染的类型集合：目录、菜单、标题；按钮类型仅作权限标识管理不下发
     *
     * @return list<self>
     */
    public static function sidebarTypes(): array
    {
        return [self::Directory, self::Menu, self::Title];
    }
}
