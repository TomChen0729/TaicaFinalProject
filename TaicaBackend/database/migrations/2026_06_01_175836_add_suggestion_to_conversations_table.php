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
        Schema::table('conversations', function (Blueprint $table) {
            // 新增一個 suggestion 欄位，允許為空 (nullable)，因為有時候使用者講得已經很完美了
            $table->text('suggestion')->nullable()->after('ai_reply');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            //
            $table->dropColumn('suggestion');
        });
    }
};
