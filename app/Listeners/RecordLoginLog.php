<?php

namespace App\Listeners;

use App\Models\LoginLog;
use App\Support\UserAgent;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

/**
 * 登录成功事件：写入登录日志
 */
class RecordLoginLog
{
    /**
     * 处理登录成功事件
     */
    public function handle(Login $event): void
    {
        $request = request();
        $userAgent = $request instanceof Request ? (string) $request->userAgent() : '';

        LoginLog::create([
            'user_id' => $event->user?->getAuthIdentifier(),
            // 优先记录登录时输入的账号，取不到时回退用户邮箱
            'account' => (string) ($request?->input('email') ?? $event->user?->email ?? ''),
            'status' => true,
            'ip' => $request instanceof Request ? (string) $request->ip() : '',
            'browser' => UserAgent::browser($userAgent),
            'platform' => UserAgent::platform($userAgent),
            'message' => '登录成功',
            'login_at' => now(),
        ]);
    }
}
