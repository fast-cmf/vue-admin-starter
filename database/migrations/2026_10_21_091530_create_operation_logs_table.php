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
        Schema::create('operation_logs', function (Blueprint $table) {
            $table->id();
            // 操作人员关联用户，账号删除后保留 username 冗余展示
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // 操作人员名称（冗余存储）
            $table->string('username')->default('');
            // 模块名称（如 媒体中心 / 邮箱设置）
            $table->string('module')->default('');
            // 操作摘要（如 媒体中心 · 修改）
            $table->string('summary')->default('');
            // 操作类型（新增 / 修改 / 删除）
            $table->string('type')->default('');
            // 操作结果：true 成功 / false 失败
            $table->boolean('status');
            // 请求地址
            $table->string('url')->default('');
            // 客户端 IP（预留 IPv6 长度）
            $table->string('ip', 45);
            // 错误信息（失败时记录）
            $table->text('error_message')->nullable();
            // 耗时（毫秒）
            $table->unsignedInteger('cost_ms')->default(0);
            // 操作时间
            $table->timestamp('performed_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_logs');
    }
};
