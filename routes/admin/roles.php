<?php

use App\Http\Controllers\Admin\Roles\RoleController;
use Illuminate\Support\Facades\Route;

// 角色权限路由：角色列表、增删改、权限矩阵同步（需登录且已验证邮箱，按权限点拦截）
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view')
        ->name('roles.index');

    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.create')
        ->name('roles.store');

    Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])
        ->middleware('permission:roles.permissions')
        ->name('roles.permissions');

    Route::patch('roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.update')
        ->name('roles.update');

    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete')
        ->name('roles.destroy');
});
