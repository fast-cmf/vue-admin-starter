<?php

namespace App\Http\Middleware;

use App\Services\MenuService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                // 当前用户全部权限点（用于前端菜单与按钮显隐）
                'can' => fn (): array => $request->user()?->getAllPermissions()->pluck('name')->values()->all() ?? [],
                // 当前用户角色名（用于前端识别 super-admin 等内置角色）
                'roles' => fn (): array => $request->user()?->getRoleNames()->values()->all() ?? [],
            ],
            // 侧边栏菜单树：按当前用户权限过滤后下发
            'menus' => fn (): array => app(MenuService::class)->sidebarFor($request->user()),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
