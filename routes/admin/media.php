<?php

use App\Http\Controllers\Admin\Media\MediaAssetController;
use Illuminate\Support\Facades\Route;

// 媒体中心路由：所有操作均需登录且已验证邮箱（挂在 /admin 前缀下），按权限点拦截
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('media', [MediaAssetController::class, 'index'])
        ->middleware('permission:media.view')
        ->name('media.index');
    Route::post('media/upload', [MediaAssetController::class, 'upload'])
        ->middleware('permission:media.upload')
        ->name('media.upload');
    Route::patch('media/{mediaAsset}', [MediaAssetController::class, 'update'])
        ->middleware('permission:media.update')
        ->name('media.update');
    Route::patch('media/{mediaAsset}/crop', [MediaAssetController::class, 'crop'])
        ->middleware('permission:media.update')
        ->name('media.crop');
    Route::delete('media/{mediaAsset}', [MediaAssetController::class, 'destroy'])
        ->middleware('permission:media.delete')
        ->name('media.destroy');
});
