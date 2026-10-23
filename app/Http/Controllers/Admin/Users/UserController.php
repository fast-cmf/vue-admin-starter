<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 用户管理控制器：用户列表、新增、编辑、拉黑与角色分配
 */
class UserController extends Controller
{
    /**
     * 用户列表页：分页展示用户及其角色，附全部可选角色
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->with('roles:id,name,title')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => $this->mapUser($user));

        $roles = Role::query()
            ->orderBy('id')
            ->get(['id', 'name', 'title', 'is_builtin']);

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * 新增后台用户：默认未验证邮箱，角色分配走独立接口
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
        ], [
            'name.required' => '请输入用户名称。',
            'email.required' => '请输入邮箱地址。',
            'email.unique' => '该邮箱已被使用。',
            'password.required' => '请输入登录密码。',
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('用户已创建。')]);

        return to_route('admin.users.index');
    }

    /**
     * 编辑用户：仅修改名称与邮箱，密码填写时才更新，角色分配走独立接口
     *
     * @throws ValidationException
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', Password::defaults()],
        ], [
            'name.required' => '请输入用户名称。',
            'email.required' => '请输入邮箱地址。',
            'email.unique' => '该邮箱已被使用。',
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (($validated['password'] ?? '') !== '') {
            $user->password = $validated['password'];
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('用户信息已更新。')]);

        return to_route('admin.users.index');
    }

    /**
     * 拉黑/解除拉黑：拉黑后立即注销其会话并禁止再次登录，超管不可被拉黑
     *
     * @throws ValidationException
     */
    public function toggleBlock(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()?->id) {
            throw ValidationException::withMessages(['blocked_at' => '不能拉黑当前登录账号。']);
        }

        if ($user->blocked_at === null && $user->hasRole('super-admin')) {
            throw ValidationException::withMessages(['blocked_at' => '超级管理员不可被拉黑，请先移除其超管角色。']);
        }

        $blocking = $user->blocked_at === null;
        $user->forceFill(['blocked_at' => $blocking ? now() : null])->save();

        if ($blocking) {
            // 立即失效其所有会话（其他设备），当前请求者不受影响
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $blocking ? __('用户已拉黑。') : __('用户已解除拉黑。'),
        ]);

        return to_route('admin.users.index');
    }

    /**
     * 为用户分配角色（全量同步），并保护系统最后一个超级管理员不被降级
     *
     * @throws ValidationException
     */
    public function assignRoles(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'roles' => ['present', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $roles = Role::query()->whereIn('id', $validated['roles'])->get();

        // 保护机制：不允许把系统中最后一个超级管理员移出 super-admin 角色，避免锁死
        $hadSuperAdmin = $user->hasRole('super-admin');
        $keepsSuperAdmin = $roles->contains(fn (Role $role): bool => $role->name === 'super-admin');

        if ($hadSuperAdmin && ! $keepsSuperAdmin) {
            $superAdminCount = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))->count();

            if ($superAdminCount <= 1) {
                throw ValidationException::withMessages([
                    'roles' => '系统至少需要保留一名超级管理员，无法移除该用户的超管角色。',
                ]);
            }
        }

        $user->syncRoles($roles);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('用户角色已更新。')]);

        return to_route('admin.users.index');
    }

    /**
     * 将用户映射为前端输出结构
     *
     * @return array{id: int, name: string, email: string, email_verified_at: string|null, blocked_at: string|null, roles: array<int, array{id: int, name: string, title: string}>, created_at: string}
     */
    private function mapUser(User $user): array
    {
        $roles = [];
        foreach ($user->roles as $role) {
            assert($role instanceof Role);
            $roles[] = ['id' => $role->id, 'name' => $role->name, 'title' => $role->title ?? $role->name];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
            'blocked_at' => $user->blocked_at?->format('Y-m-d H:i:s'),
            'roles' => $roles,
            'created_at' => $user->created_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }
}
