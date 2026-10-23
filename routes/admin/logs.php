<?php

use App\Http\Controllers\Admin\Logs\LoginLogController;
use App\Http\Controllers\Admin\Logs\OperationLogController;
use Illuminate\Support\Facades\Route;

// 系统日志路由：登录日志与操作日志的列表、导出、批量删除与清空（需登录且已验证邮箱），按权限点拦截
Route::middleware(['auth', 'verified'])->group(function () {
    // 登录日志
    Route::get('logs/login', [LoginLogController::class, 'index'])
        ->middleware('permission:logs.view')
        ->name('login-logs.index');
    Route::get('logs/login/export', [LoginLogController::class, 'export'])
        ->middleware('permission:logs.export')
        ->name('login-logs.export');
    Route::delete('logs/login', [LoginLogController::class, 'clear'])
        ->middleware('permission:logs.clear')
        ->name('login-logs.clear');
    Route::delete('logs/login/destroy', [LoginLogController::class, 'destroy'])
        ->middleware('permission:logs.clear')
        ->name('login-logs.destroy');

    // 操作日志
    Route::get('logs/operation', [OperationLogController::class, 'index'])
        ->middleware('permission:logs.view')
        ->name('operation-logs.index');
    Route::get('logs/operation/export', [OperationLogController::class, 'export'])
        ->middleware('permission:logs.export')
        ->name('operation-logs.export');
    Route::delete('logs/operation', [OperationLogController::class, 'clear'])
        ->middleware('permission:logs.clear')
        ->name('operation-logs.clear');
    Route::delete('logs/operation/destroy', [OperationLogController::class, 'destroy'])
        ->middleware('permission:logs.clear')
        ->name('operation-logs.destroy');
});
