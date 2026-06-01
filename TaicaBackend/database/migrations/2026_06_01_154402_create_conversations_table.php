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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            // 建立與 users 資料表的外鍵關聯，當會員帳號被刪除時，其對話紀錄也會一併刪除 (cascade)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('scenario_id');                 // 情境代號 (例如：fast_food, supermarket)
            $table->text('user_text');                     // 儲存 Whisper 辨識出的人類口說文字
            $table->text('ai_reply');                      // 儲存 Gemma2 產生的 AI 回覆文字
            $table->boolean('is_success')->default(false); // 紀錄該回合是否成功達成任務 (預設為失敗)
            $table->timestamps();                          // 自動產生 created_at (對話時間) 與 updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
