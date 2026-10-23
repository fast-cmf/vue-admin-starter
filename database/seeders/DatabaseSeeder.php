<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * 默认 Seeder：写入内置角色、权限点与初始菜单树
 *
 * 推荐使用 php artisan app:install 完成安装（会顺带执行迁移并创建首个超级管理员），
 * 本 Seeder 保持幂等，可单独执行 db:seed 重新初始化基础数据
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 内置角色与注册表权限点
        $this->call(RolesSeeder::class);

        // 初始菜单树
        $this->call(MenusSeeder::class);
    }
}
