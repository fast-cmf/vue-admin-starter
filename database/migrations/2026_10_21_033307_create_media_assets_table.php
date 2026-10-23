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
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable()->comment('显示名称，默认取文件名');
            $table->string('folder')->nullable()->index()->comment('分组目录');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete()->comment('上传人');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
