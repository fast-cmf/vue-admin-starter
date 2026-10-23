<?php

namespace App\Listeners;

use App\Models\LoginLog;
use App\Models\User;
use App\Support\UserAgent;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;

/**
 * 登录失败事件：写入登录日志（账号不存在或密码错误）
 */
class RecordFailedLogin
{
    /**
     * 处理登录失败事件
     */
    public function handle(Failed $event): void
    {
        $request = request();
        $userAgent = $request instanceof Request ? (string) $request->userAgent() : '';

        LoginLog::create([
            // 账号存在时事件会携带该用户（如密码错误、被拉黑拒绝）；
            // 自定义登录校验（拉黑拦截）触发的事件不带用户，按邮箱补查关联
            'user_id' => ($event->user ?? $this->resolveUser($event))?->getAuthIdentifier(),
            'account' => (string) ($event->credentials['email'] ?? $request?->input('email') ?? ''),
            'status' => false,
            'ip' => $request instanceof Request ? (string) $request->ip() : '',
            'browser' => UserAgent::browser($userAgent),
            'platform' => UserAgent::platform($userAgent),
            'message' => '账号或密码错误',
            'login_at' => now(),
        ]);
    }

    /**
     * 事件未携带用户时按邮箱补查（事件本身不区分「密码错误」与「被拉黑」）
     */
    private function resolveUser(Failed $event): ?User
    {
        $email = $event->credentials['email'] ?? null;

        if (! is_string($email) || $email === '') {
            return null;
        }

        return User::query()->where('email', $email)->first();
    }
}
