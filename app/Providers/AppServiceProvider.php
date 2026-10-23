<?php

namespace App\Providers;

use App\Listeners\RecordFailedLogin;
use App\Listeners\RecordLoginLog;
use App\Models\User;
use App\Settings\MailSettings;
use App\Settings\StorageSettings;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        $this->registerLoginLogListeners();

        $this->registerSuperAdminGate();

        $this->rejectBlockedUsersAtLogin();
    }

    /**
     * 登录校验：密码正确但已被拉黑的账号拒绝登录（与普通密码错误返回同样的提示）
     */
    protected function rejectBlockedUsersAtLogin(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()->where('email', $request->email)->first();

            if ($user === null || $user->isBlocked() || ! Hash::check((string) $request->password, $user->password)) {
                return null;
            }

            return $user;
        });
    }

    /**
     * 超级管理员放行：super-admin 角色绕过全部权限检查，无需逐一挂载权限
     */
    protected function registerSuperAdminGate(): void
    {
        Gate::before(function (?User $user, string $ability): ?bool {
            return $user?->hasRole('super-admin') ? true : null;
        });
    }

    /**
     * 注册登录日志监听：成功与失败登录均写入 login_logs 表
     */
    protected function registerLoginLogListeners(): void
    {
        Event::listen(Login::class, RecordLoginLog::class);
        Event::listen(Failed::class, RecordFailedLogin::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        $this->registerOssDisk();

        $this->registerSmtpMailer();

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * 按存储设置动态注册 oss 磁盘（S3 兼容协议）；
     * 设置表不可用（如全新环境未迁移）时静默跳过，媒体库回落本地磁盘
     */
    protected function registerOssDisk(): void
    {
        try {
            $settings = app(StorageSettings::class);

            // 属性访问会懒加载触发数据库查询，必须一并纳入容错
            if ($settings->disk !== 'oss' || ! $settings->isOssConfigured()) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        config([
            'filesystems.disks.oss' => [
                'driver' => 's3',
                'key' => $settings->oss_access_key_id,
                'secret' => $settings->oss_secret_access_key,
                'region' => $settings->oss_region !== '' ? $settings->oss_region : 'us-east-1',
                'bucket' => $settings->oss_bucket,
                'url' => $settings->oss_url !== '' ? $settings->oss_url : null,
                'endpoint' => $settings->oss_endpoint,
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],
        ]);
    }

    /**
     * 按邮箱设置动态覆盖 SMTP mailer 与发件人配置；
     * 设置表不可用（如全新环境未迁移）时静默跳过，回落框架默认 mail 配置
     */
    protected function registerSmtpMailer(): void
    {
        try {
            $settings = app(MailSettings::class);

            // 属性访问会懒加载触发数据库查询，必须一并纳入容错
            if (! $settings->enabled || ! $settings->isSmtpConfigured()) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        config([
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'host' => $settings->smtp_host,
                'port' => $settings->smtp_port,
                'encryption' => $settings->smtpEncryptionScheme(),
                'username' => $settings->smtp_username,
                'password' => $settings->smtp_password,
                'timeout' => null,
            ],
            'mail.from.address' => $settings->from_email,
            'mail.from.name' => $settings->from_name !== '' ? $settings->from_name : config('app.name'),
        ]);
    }
}
