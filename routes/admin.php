<?php

use App\Http\Middleware\LogOperation;

// 后台管理路由：统一 /admin 前缀 + admin. 路由名前缀，子文件内各自声明认证中间件
Route::prefix('admin')->name('admin.')->group(function () {
    // 后台首页：需登录且已验证邮箱
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::inertia('dashboard', 'admin/Dashboard')
            ->middleware('permission:dashboard.view')
            ->name('dashboard');
        require __DIR__.'/admin/docs.php';
    });

    // 后台写操作统一记录操作日志
    Route::middleware([LogOperation::class])->group(function () {
        require __DIR__.'/admin/media.php';
        require __DIR__.'/admin/settings.php';
        require __DIR__.'/admin/logs.php';
        require __DIR__.'/admin/roles.php';
        require __DIR__.'/admin/users.php';
        require __DIR__.'/admin/menus.php';
    });
});
