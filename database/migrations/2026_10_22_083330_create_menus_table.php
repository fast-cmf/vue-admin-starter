<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            // 树形自关联：应用层拦截删除有子菜单的节点，cascade 仅作数据库兜底
            $table->foreignId('parent_id')->nullable()->constrained('menus')->cascadeOnDelete();
            // 菜单类型：directory 目录 / menu 菜单 / title 标题 / button 按钮
            $table->string('type', 16);
            $table->string('title', 50);
            // 跳转地址：menu 类型必填，/admin/... 站内路径或 http(s) 外链
            $table->string('path')->nullable();
            // 图标名（PascalCase），需在 config/menus.php icons 白名单内
            $table->string('icon')->nullable();
            $table->integer('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            // 绑定的权限点（config/permissions.php 注册表 key），为空则登录即可见
            $table->string('permission')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
