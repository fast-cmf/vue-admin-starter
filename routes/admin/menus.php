<?php

use App\Http\Controllers\Admin\Menus\MenuController;
use Illuminate\Support\Facades\Route;

// 菜单管理路由：菜单树列表、创建、编辑与删除（需登录且已验证邮箱，按权限点拦截）
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('menus', [MenuController::class, 'index'])
        ->middleware('permission:menus.view')
        ->name('menus.index');

    Route::post('menus', [MenuController::class, 'store'])
        ->middleware('permission:menus.create')
        ->name('menus.store');

    Route::patch('menus/{menu}', [MenuController::class, 'update'])
        ->middleware('permission:menus.update')
        ->name('menus.update');

    Route::delete('menus/{menu}', [MenuController::class, 'destroy'])
        ->middleware('permission:menus.delete')
        ->name('menus.destroy');
});
