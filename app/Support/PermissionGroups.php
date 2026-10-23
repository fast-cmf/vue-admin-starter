<?php

namespace App\Support;

/**
 * 权限注册表分组工具：把 config/permissions.php 的扁平权限点按 group 分组，
 * 供权限矩阵与菜单管理的权限点下拉共用
 */
class PermissionGroups
{
    /**
     * 从注册表构建权限分组：[分组名 => [权限点 => 中文标签]]
     *
     * @return array<string, array<string, string>>
     */
    public static function fromRegistry(): array
    {
        $groups = [];

        foreach ((array) config('permissions') as $name => $meta) {
            $groups[$meta['group']][$name] = $meta['label'];
        }

        return $groups;
    }
}
