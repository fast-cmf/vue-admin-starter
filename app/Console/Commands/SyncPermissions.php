<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * 权限同步命令：将 config/permissions.php 权限注册表同步进 permissions 表，
 * 并对路由上 permission: 中间件使用的权限点做漂移检查（注册表中不存在的会告警）
 *
 * 用法：php artisan permission:sync
 */
class SyncPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步权限注册表到数据库并检查路由权限漂移';

    /**
     * Execute the console command.
     */
    public function handle(PermissionRegistrar $registrar): int
    {
        $this->syncPermissions();

        $this->checkDrift();

        // 直接触碰权限/角色关联后统一清缓存，保证权限判定即时生效
        $registrar->forgetCachedPermissions();

        $this->info('权限同步完成。');

        return self::SUCCESS;
    }

    /**
     * 将注册表权限点 upsert 进 permissions 表：
     * 只新增与更新，不删除库中已有但注册表未登记的权限点，也不触碰角色关联
     */
    protected function syncPermissions(): void
    {
        $registry = config('permissions');
        $guard = config('auth.defaults.guard', 'web');

        $created = 0;
        $updated = 0;

        foreach ($registry as $name => $meta) {
            $permission = Permission::query()->firstWhere([
                'name' => $name,
                'guard_name' => $guard,
            ]);

            if ($permission === null) {
                Permission::query()->create(['name' => $name, 'guard_name' => $guard]);
                $created++;

                continue;
            }

            // 权限点标识无需更新，此处仅占位以便未来扩展描述字段
            $updated++;
        }

        $this->info(sprintf('注册表权限点 %d 个：新增 %d 个，保留 %d 个。', count($registry), $created, $updated));
    }

    /**
     * 漂移检查：遍历全部路由收集 permission: 中间件参数，
     * 输出注册表中不存在的权限点，避免路由引用了未登记的权限导致整段路由不可用
     */
    protected function checkDrift(): void
    {
        $registry = array_keys((array) config('permissions'));

        $unknown = collect(app(Router::class)->getRoutes()->getRoutes())
            ->flatMap(function (Route $route): array {
                $permissions = collect($route->gatherMiddleware())
                    ->filter(fn ($middleware): bool => is_string($middleware) && Str::startsWith($middleware, 'permission:'))
                    ->flatMap(fn (string $middleware): array => explode('|', substr($middleware, strlen('permission:'))))
                    ->all();

                return collect($permissions)
                    ->map(fn (string $permission): string => trim($permission))
                    ->filter(fn (string $permission): bool => $permission !== '')
                    ->map(fn (string $permission): array => [$permission, $route->getName() ?? $route->uri()])
                    ->all();
            })
            ->filter(fn (array $item): bool => ! in_array($item[0], $registry, true))
            ->unique(fn (array $item): string => $item[0])
            ->values();

        foreach ($unknown as [$permission, $route]) {
            $this->warn(sprintf('漂移：权限点 [%s] 被路由 [%s] 使用，但未在 config/permissions.php 中登记。', $permission, $route));
        }

        if ($unknown->isEmpty()) {
            $this->line('漂移检查通过：所有路由引用的权限点均已在注册表中登记。');
        }

        // 顺带提示：内置角色数量，帮助确认角色种子已执行
        $builtinCount = Role::query()->where('is_builtin', true)->count();
        $this->line(sprintf('当前内置角色 %d 个。', $builtinCount));
    }
}
