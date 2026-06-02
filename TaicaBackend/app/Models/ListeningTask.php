<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ListeningTask extends Model
{
    use HasFactory;

    // 關閉自動遞增，並將主鍵型態設為字串
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'title', 'script', 'question', 'options', 'correct_answer', 'suggestion'
    ];

    // 讓 Laravel 自動把資料庫的 JSON 字串轉換回 PHP 陣列
    protected $casts = [
        'options' => 'array',
    ];
}
