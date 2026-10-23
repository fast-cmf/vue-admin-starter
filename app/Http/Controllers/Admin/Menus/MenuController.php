<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Enums\MenuType;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Support\PermissionGroups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 菜单管理控制器：菜单树列表、创建、编辑与删除
 */
class MenuController extends Controller
{
    /**
     * 菜单列表页：下发全量菜单树（管理用，不过滤权限）及表单辅助数据
     */
    public function index(): Response
    {
        // 标题类型不再开放创建（仅保留枚举以兼容历史数据），表单仅提供目录/菜单/按钮
        return Inertia::render('admin/menus/Index', [
            'tree' => $this->buildTree(),
            'types' => collect(MenuType::options())->except(MenuType::Title->value)->all(),
            'parents' => $this->parentOptions(),
            'groups' => PermissionGroups::fromRegistry(),
            'icons' => array_values((array) config('menus.icons')),
            'pages' => $this->pageOptions(),
        ]);
    }

    /**
     * 创建菜单
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateMenu($request);

        Menu::query()->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('菜单已创建。')]);

        return to_route('admin.menus.index');
    }

    /**
     * 编辑菜单
     *
     * @throws ValidationException
     */
    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $this->validateMenu($request, $menu);

        $menu->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('菜单已更新。')]);

        return to_route('admin.menus.index');
    }

    /**
     * 删除菜单：存在子菜单时拒绝，提示先删除子级
     *
     * @throws ValidationException
     */
    public function destroy(Menu $menu): RedirectResponse
    {
        if ($menu->hasChildren()) {
            throw ValidationException::withMessages([
                'menu' => '该菜单存在子菜单，请先删除子菜单。',
            ]);
        }

        $menu->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('菜单已删除。')]);

        return to_route('admin.menus.index');
    }

    /**
     * 菜单创建/编辑公共校验
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function validateMenu(Request $request, ?Menu $menu = null): array
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists('menus', 'id')],
            'type' => ['required', Rule::enum(MenuType::class)],
            'title' => ['required', 'string', 'max:50'],
            // path：menu 类型必填，站内相对路径（/ 开头），后台不提供外链
            'path' => ['nullable', 'string', 'max:500', 'required_if:type,'.MenuType::Menu->value, 'prohibited_unless:type,'.MenuType::Menu->value, 'starts_with:/'],
            'icon' => ['nullable', 'string', Rule::in(array_values((array) config('menus.icons')))],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_visible' => ['nullable', 'boolean'],
            'permission' => ['nullable', 'string', Rule::in(array_keys((array) config('permissions')))],
        ], [
            'title.required' => '请输入菜单名称。',
            'title.max' => '菜单名称不能超过 50 个字符。',
            'type.required' => '请选择菜单类型。',
            'type.enum' => '菜单类型无效。',
            'path.required_if' => '菜单类型为「菜单」时必须填写跳转地址。',
            'path.prohibited_unless' => '仅「菜单」类型需要跳转地址。',
            'path.starts_with' => '跳转地址必须以 / 开头。',
            'icon.in' => '图标不在可用列表中。',
            'permission.in' => '权限标识无效。',
            'parent_id.exists' => '所选父级菜单不存在。',
        ]);

        // 站内相对路径前缀校验
        if (! empty($validated['path']) && ! str_starts_with((string) $validated['path'], '/')) {
            throw ValidationException::withMessages([
                'path' => '跳转地址必须以 / 开头。',
            ]);
        }

        // 父级合法性：目录可作任意子级的父级；菜单仅可作按钮的父级（页面内权限标识）；不能是自身或其后代（防环）
        if (! empty($validated['parent_id'])) {
            $parent = Menu::query()->whereKey((int) $validated['parent_id'])->first();

            if ($parent === null || ! in_array($parent->type, [MenuType::Directory, MenuType::Menu], true)) {
                throw ValidationException::withMessages([
                    'parent_id' => '父级必须是「目录」或「菜单」类型。',
                ]);
            }

            if ($parent->type === MenuType::Menu && $validated['type'] !== MenuType::Button->value) {
                throw ValidationException::withMessages([
                    'parent_id' => '「菜单」类型下只能挂「按钮」类型的子项。',
                ]);
            }

            if ($menu !== null && $parent->isDescendantOfOrSelf($menu)) {
                throw ValidationException::withMessages([
                    'parent_id' => '父级不能是自身或其子级菜单。',
                ]);
            }
        }

        $validated['parent_id'] = $validated['parent_id'] ?? null;
        $validated['sort'] = $validated['sort'] ?? 0;
        $validated['is_visible'] = (bool) ($validated['is_visible'] ?? true);

        return $validated;
    }

    /**
     * 构建管理用全量菜单树（不过滤权限与可见性）
     *
     * @return list<array<string, mixed>>
     */
    protected function buildTree(?int $parentId = null): array
    {
        $rows = [];

        $menus = Menu::query()
            ->where('parent_id', $parentId)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        foreach ($menus as $menu) {
            $rows[] = [
                'id' => $menu->id,
                'parent_id' => $menu->parent_id,
                'type' => $menu->type->value,
                'type_label' => $menu->type->label(),
                'title' => $menu->title,
                'path' => $menu->path,
                'icon' => $menu->icon,
                'sort' => $menu->sort,
                'is_visible' => $menu->is_visible,
                'permission' => $menu->permission,
                'children' => $this->buildTree($menu->id),
            ];
        }

        return $rows;
    }

    /**
     * 可选页面：枚举后台已注册的 GET 页面路由（无参数、非导出类），供跳转地址下拉选择
     *
     * @return list<array{path: string, name: string}>
     */
    protected function pageOptions(): array
    {
        $pages = [];

        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();

            if (! str_starts_with($name, 'admin.') || ! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            // 排除带路由参数与导出类接口，仅保留可作菜单目标的页面
            if (str_contains($uri, '{') || str_contains($uri, 'export')) {
                continue;
            }

            $pages[] = [
                'path' => '/'.$uri,
                'name' => $name,
            ];
        }

        usort($pages, fn (array $a, array $b): int => strcmp($a['path'], $b['path']));

        return $pages;
    }

    /**
     * 可选父级：树形扁平列表，仅含目录（可挂任意子级）与菜单（仅可挂按钮），按层级顺序展开
     *
     * @return list<array{id: int, title: string, type: string, depth: int}>
     */
    protected function parentOptions(): array
    {
        $rows = [];

        $menus = Menu::query()
            ->whereIn('type', [MenuType::Directory, MenuType::Menu])
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'title', 'type']);

        $byParent = $menus->groupBy('parent_id');

        $walk = function (?int $parentId, int $depth) use (&$walk, $byParent, &$rows): void {
            foreach ($byParent->get($parentId, collect()) as $menu) {
                $rows[] = [
                    'id' => $menu->id,
                    'title' => $menu->title,
                    'type' => $menu->type->value,
                    'depth' => $depth,
                ];

                $walk($menu->id, $depth + 1);
            }
        };

        $walk(null, 0);

        return $rows;
    }
}
