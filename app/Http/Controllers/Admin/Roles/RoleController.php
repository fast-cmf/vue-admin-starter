<?php

namespace App\Http\Controllers\Admin\Roles;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

/**
 * 角色权限控制器：角色列表、创建、改名、删除与权限矩阵同步
 */
class RoleController extends Controller
{
    /**
     * 角色列表页：返回角色（含用户数与权限点）及权限矩阵分组数据
     */
    public function index(): Response
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('id')
            ->get()
            ->map(fn (Role $role): array => $this->mapRole($role));

        return Inertia::render('admin/roles/Index', [
            'roles' => $roles,
            // 权限矩阵分组：[分组名 => [权限点 => 中文标签]]
            'groups' => $this->permissionGroups(),
        ]);
    }

    /**
     * 创建角色：title 为显示名称，name 为创建后不可变的代码标识
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/', $this->uniqueNameRule()],
        ], [
            'title.required' => '请输入角色名称。',
            'title.max' => '角色名称不能超过 50 个字符。',
            'name.required' => '请输入角色标识。',
            'name.max' => '角色标识不能超过 100 个字符。',
            'name.regex' => '角色标识只能包含英文字母、数字、短横线和下划线。',
            'name.unique' => '该角色标识已存在。',
        ]);

        Role::query()->create([
            'name' => $validated['name'],
            'title' => $validated['title'],
            'guard_name' => config('auth.defaults.guard', 'web'),
            'is_builtin' => false,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('角色已创建。')]);

        return to_route('admin.roles.index');
    }

    /**
     * 修改角色显示名称（内置角色不可修改，标识 name 创建后不可变更）
     *
     * @throws ValidationException
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->is_builtin) {
            throw ValidationException::withMessages(['title' => '内置角色不可修改。']);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:50'],
        ], [
            'title.required' => '请输入角色名称。',
            'title.max' => '角色名称不能超过 50 个字符。',
        ]);

        $role->update(['title' => $validated['title']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('角色名称已更新。')]);

        return to_route('admin.roles.index');
    }

    /**
     * 同步角色权限矩阵
     */
    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys((array) config('permissions')))],
        ]);

        $role->syncPermissions($validated['permissions']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('权限配置已保存。')]);

        return to_route('admin.roles.index');
    }

    /**
     * 删除角色（内置角色不可删除）
     *
     * @throws ValidationException
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_builtin) {
            throw ValidationException::withMessages(['title' => '内置角色不可删除。']);
        }

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('角色已删除。')]);

        return to_route('admin.roles.index');
    }

    /**
     * 角色名称唯一性校验规则（同 guard 下）
     */
    private function uniqueNameRule(?Role $ignore = null): Rules\Unique
    {
        $rule = Rule::unique('roles', 'name')->where('guard_name', config('auth.defaults.guard', 'web'));

        return $ignore === null ? $rule : $rule->ignore($ignore->getKey());
    }

    /**
     * 将角色映射为前端输出结构
     *
     * @return array{id: int, name: string, title: string, is_builtin: bool, users_count: int, permissions_count: int, permissions: array<int, string>, created_at: string}
     */
    private function mapRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'title' => $role->title ?? $role->name,
            'is_builtin' => $role->is_builtin,
            'users_count' => $role->users_count,
            'permissions_count' => $role->permissions_count,
            'permissions' => $role->permissions->map(fn (Permission $permission): string => $permission->name)->values()->all(),
            'created_at' => $role->created_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    /**
     * 从注册表构建权限矩阵分组：[分组名 => [权限点 => 中文标签]]
     *
     * @return array<string, array<string, string>>
     */
    private function permissionGroups(): array
    {
        $groups = [];

        foreach ((array) config('permissions') as $name => $meta) {
            $groups[$meta['group']][$name] = $meta['label'];
        }

        return $groups;
    }
}
