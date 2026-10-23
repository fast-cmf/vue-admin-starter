<?php

// 后台使用文档：纯静态展示页，无需控制器，直接渲染 Inertia 组件
Route::inertia('docs', 'admin/docs/Index')->name('docs');
