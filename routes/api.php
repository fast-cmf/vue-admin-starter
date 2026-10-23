<?php

// API 路由聚合入口：统一挂 /api 前缀（由 withRouting(api:) 注册）+ v1 版本前缀
Route::prefix('v1')->name('api.v1.')->group(function () {
    require __DIR__.'/api/v1.php';
});
