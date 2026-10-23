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
        // 内置角色标记：内置角色（super-admin、editor、viewer）不可在后台编辑与删除
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_builtin')->default(false)->after('guard_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_builtin');
        });
    }
};
