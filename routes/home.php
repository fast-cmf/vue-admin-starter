<?php

// 前台门户路由：公开访问，无需登录
Route::inertia('/', 'home/Index')->name('home');

// Passkeys 客户端发现端点（公开，指向后台安全设置页）
Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('admin.security.edit'),
        'manage' => route('admin.security.edit'),
    ]);
})->name('well-known.passkeys');
