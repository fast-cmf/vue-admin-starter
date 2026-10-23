<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\MenusSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * 应用安装命令：一步完成数据库迁移、初始数据写入与首个超级管理员创建
 *
 * 用法：php artisan app:install
 * 也可带参数非交互执行：php artisan app:install --name=管理员 --email=admin@example.com --password=secret123
 * 幂等保护：已有用户时拒绝重复安装
 */
class InstallApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install
        {--name= : 超级管理员姓名}
        {--email= : 超级管理员邮箱}
        {--password= : 超级管理员密码（至少 8 位）}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '安装应用：迁移数据库、写入初始角色与菜单、创建首个超级管理员';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 幂等保护：只要已有用户即视为已安装，避免重复执行覆盖数据
        if (User::query()->exists()) {
            $this->warn('应用已安装（已存在用户），如需重新安装请先清空数据库。');

            return self::FAILURE;
        }

        $this->components->info('开始安装…');

        // 1. 数据库迁移
        $this->call('migrate', ['--force' => true]);

        // 2. 初始数据：内置角色 + 权限点 + 初始菜单树
        $this->call('db:seed', ['--class' => RolesSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => MenusSeeder::class, '--force' => true]);
        $this->call('permission:sync');

        // 3. 创建首个超级管理员
        $credentials = $this->collectAdminCredentials();
        if ($credentials === null) {
            return self::FAILURE;
        }

        $admin = User::query()->create([
            'name' => $credentials['name'],
            'email' => $credentials['email'],
            'password' => Hash::make($credentials['password']),
        ]);
        // 首个管理员直接标记邮箱已验证（email_verified_at 不在 Fillable 白名单内，需 forceFill）
        $admin->forceFill(['email_verified_at' => now()])->save();
        $admin->assignRole('super-admin');

        $this->components->info('安装完成！');
        $this->line(sprintf('超级管理员：%s <%s>', $admin->name, $admin->email));
        $this->line('请访问 /admin/login 登录后台。');

        return self::SUCCESS;
    }

    /**
     * 收集并校验管理员信息：优先取命令行选项，缺失则交互询问
     *
     * @return array{name: string, email: string, password: string}|null 校验失败返回 null
     */
    protected function collectAdminCredentials(): ?array
    {
        $name = $this->option('name') ?? $this->ask('超级管理员姓名');
        $email = $this->option('email') ?? $this->ask('超级管理员邮箱');
        $password = $this->option('password') ?? $this->secret('超级管理员密码（至少 8 位）');

        // 参数校验：邮箱格式唯一、密码强度，失败则终止安装
        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return null;
        }

        return ['name' => $name, 'email' => $email, 'password' => $password];
    }
}
