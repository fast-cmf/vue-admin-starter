<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * 初始角色 Seeder：仅写入内置超级管理员（Gate::before 全放行），其余角色按需在后台创建
 */
class RolesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');

        // 确保注册表权限点全部入库（供角色挂载与路由中间件校验）
        foreach (array_keys((array) config('permissions')) as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        Role::query()->updateOrCreate(
            ['name' => 'super-admin', 'guard_name' => $guard],
            ['title' => '超级管理员', 'is_builtin' => true],
        )->syncPermissions(array_keys((array) config('permissions')));
    }
}
