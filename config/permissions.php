<?php

// 权限注册表：全站权限标识的唯一来源
// key = 权限标识「模块.动作」，label = 中文标签，group = 权限矩阵中的中文分组
// 新增后台模块时在此登记权限标识，并在对应路由上挂 permission:中间件，然后运行 php artisan permission:sync 写入数据库
// 权限矩阵界面与初始角色 Seeder 均从本文件读取

return [
    // 仪表盘
    'dashboard.view' => ['label' => '查看仪表盘', 'group' => '仪表盘'],

    // 媒体中心
    'media.view' => ['label' => '查看媒体', 'group' => '媒体中心'],
    'media.upload' => ['label' => '上传媒体', 'group' => '媒体中心'],
    'media.update' => ['label' => '编辑媒体', 'group' => '媒体中心'],
    'media.delete' => ['label' => '删除媒体', 'group' => '媒体中心'],

    //用户管理
    'users.view' => ['label' => '查看用户', 'group' => '用户管理'],
    'users.create' => ['label' => '新增用户', 'group' => '用户管理'],
    'users.update' => ['label' => '编辑用户', 'group' => '用户管理'],
    'users.block' => ['label' => '拉黑用户', 'group' => '用户管理'],
    'users.manage' => ['label' => '用户角色', 'group' => '用户管理'],

    //菜单管理
    'menus.view' => ['label' => '查看菜单', 'group' => '菜单管理'],
    'menus.delete' => ['label' => '删除菜单', 'group' => '菜单管理'],
    'menus.create' => ['label' => '新增菜单', 'group' => '菜单管理'],
    'menus.update' => ['label' => '编辑菜单', 'group' => '菜单管理'],


    //角色管理
    'roles.view' => ['label' => '查看角色', 'group' => '角色管理'],
    'roles.permissions' => ['label' => '权限配置', 'group' => '角色管理'],
    'roles.delete' => ['label' => '删除角色', 'group' => '角色管理'],
    'roles.create' => ['label' => '新增角色', 'group' => '角色管理'],
    'roles.update' => ['label' => '编辑角色', 'group' => '角色管理'],


    // 日志管理
    'logs.view' => ['label' => '查看日志', 'group' => '日志管理'],
    'logs.export' => ['label' => '导出日志', 'group' => '日志管理'],
    'logs.clear' => ['label' => '删除与清空日志', 'group' => '日志管理'],


    // 系统管理
    'settings.storage' => ['label' => '管理存储设置', 'group' => '系统设置'],
    'settings.mail' => ['label' => '管理邮箱设置', 'group' => '系统设置'],
    'settings.testmail' => ['label' => '发送测试邮件', 'group' => '系统设置'],
];
