<?php

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * app:install 安装命令测试：从零安装、幂等保护与参数校验
 */
test('install command performs fresh install with super admin', function () {
    $this->artisan('app:install', [
        '--name' => '管理员',
        '--email' => 'admin@example.com',
        '--password' => 'secret1234',
    ])->assertSuccessful();

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    expect($admin->name)->toBe('管理员')
        ->and(Hash::check('secret1234', $admin->password))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and($admin->hasRole('super-admin'))->toBeTrue()
        // 初始角色与菜单树均已写入
        ->and(Role::query()->where('name', 'super-admin')->exists())->toBeTrue()
        ->and(Menu::query()->where('title', '仪表盘')->exists())->toBeTrue();
});

test('install command refuses to run when already installed', function () {
    User::factory()->create();

    $this->artisan('app:install', [
        '--name' => '管理员',
        '--email' => 'admin@example.com',
        '--password' => 'secret1234',
    ])->assertFailed();

    expect(User::query()->where('email', 'admin@example.com')->exists())->toBeFalse();
});

test('install command validates credentials and aborts', function () {
    $this->artisan('app:install', [
        '--name' => '管理员',
        '--email' => 'not-an-email',
        '--password' => 'short',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});
