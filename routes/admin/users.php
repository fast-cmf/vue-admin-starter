<?php

use App\Http\Controllers\Admin\Users\UserController;
use Illuminate\Support\Facades\Route;

// 用户管理路由：用户列表与角色分配（需登录且已验证邮箱，按权限点拦截）
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('users', [UserController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('users.index');

    Route::post('users', [UserController::class, 'store'])
        ->middleware('permission:users.create')
        ->name('users.store');

    Route::put('users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.update')
        ->name('users.update');

    Route::patch('users/{user}/block', [UserController::class, 'toggleBlock'])
        ->middleware('permission:users.block')
        ->name('users.toggleBlock');

    Route::put('users/{user}/roles', [UserController::class, 'assignRoles'])
        ->middleware('permission:users.manage')
        ->name('users.assignRoles');
});
