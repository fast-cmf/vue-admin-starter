<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * roles 表新增 title 列：角色中文名称，name 降级为创建后不可变的代码标识
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('title', 100)->nullable()->after('name')->comment('角色名称（中文显示名）');
        });

        // 回填：内置角色用中文显示名，自定义角色暂以 name 兜底
        DB::table('roles')->where('name', 'super-admin')->update(['title' => '超级管理员']);
        DB::table('roles')->where('name', 'editor')->update(['title' => '运营编辑']);
        DB::table('roles')->where('name', 'viewer')->update(['title' => '只读']);
        DB::table('roles')->whereNull('title')->update(['title' => DB::raw('`name`')]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
