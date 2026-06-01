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
        Schema::create('scenarios', function (Blueprint $table) {
            $table->string('id')->primary(); // 改用字串當主鍵，例如 'fast_food'
            $table->string('title');         // 標題 (e.g., 🍔 Fast Food Drive-Thru)
            $table->string('task');          // 任務敘述
            $table->string('greeting');      // AI 開場白
            $table->string('color', 7);      // 色碼 (e.g., #ef4444)
            $table->text('system_prompt');   // 屬於該情境的 AI 角色提示詞
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scenarios');
    }
};
