<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
class Conversation extends Model
{
    use HasFactory;

    // 定義允許寫入資料庫的欄位
    protected $fillable = [
        'user_id',
        'scenario_id',
        'user_text',
        'ai_reply',
        'is_success',
        'suggestion',
    ];

    /**
     * 定義關聯：一筆對話紀錄屬於一個使用者
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
