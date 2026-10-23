<?php

use App\Http\Controllers\Admin\Settings\MailController;
use App\Http\Controllers\Admin\Settings\ProfileController;
use App\Http\Controllers\Admin\Settings\SecurityController;
use App\Http\Controllers\Admin\Settings\StorageController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

// 个人设置路由：挂在 /admin 前缀下（由 routes/admin.php 统一包裹）
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/admin/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::get('settings/storage', [StorageController::class, 'edit'])
        ->middleware('permission:settings.storage')
        ->name('storage.edit');
    Route::patch('settings/storage', [StorageController::class, 'update'])
        ->middleware('permission:settings.storage')
        ->name('storage.update');

    Route::get('settings/mail', [MailController::class, 'edit'])
        ->middleware('permission:settings.mail')
        ->name('mail.edit');
    Route::patch('settings/mail', [MailController::class, 'update'])
        ->middleware('permission:settings.mail')
        ->name('mail.update');
    Route::post('settings/mail/send-test', [MailController::class, 'sendTest'])
        ->middleware(['throttle:3,1', 'permission:settings.testmail'])
        ->name('mail.sendTest');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'admin/settings/Appearance')
        ->name('appearance.edit');
});
