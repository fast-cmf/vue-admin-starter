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
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            // 登录成功时关联的用户，失败或账号不存在时为空
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // 登录时输入的账号（邮箱）
            $table->string('account');
            // 登录状态：true 成功 / false 失败
            $table->boolean('status');
            // 客户端 IP（预留 IPv6 长度）
            $table->string('ip', 45);
            // 浏览器（如 Chrome 155.0.0.0）
            $table->string('browser')->default('');
            // 操作系统（如 Windows 10）
            $table->string('platform')->default('');
            // 提示信息（如 登录成功 / 账号或密码错误）
            $table->string('message')->default('');
            // 登录时间
            $table->timestamp('login_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
