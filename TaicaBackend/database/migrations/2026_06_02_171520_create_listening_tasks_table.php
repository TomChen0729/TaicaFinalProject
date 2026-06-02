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
        Schema::create('listening_tasks', function (Blueprint $table) {
            $table->string('id')->primary(); // 我們用字串當主鍵，例如 'fast_food_pickup'
            $table->string('title');
            $table->text('script'); // 英文聽力腳本原文
            $table->string('question'); // 中文問題
            $table->json('options'); // 儲存選項 A, B, C (使用 JSON 格式)
            $table->string('correct_answer', 10); // 正確解答 (例如 'B')
            $table->text('suggestion')->nullable(); // 給學生的建議
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listening_tasks');
    }
};
