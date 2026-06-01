<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ChatService;
use Illuminate\Support\Facades\Log;
use Exception;

class ChatController extends Controller
{
    protected ChatService $chatService;

    // 透過建構子注入 ChatService
    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function handleVoiceChat(Request $request)
    {
        // 1. 嚴格驗證請求：確保音檔與情境參數都有傳送
        $request->validate([
            'audio' => 'required|file',
            'scenario' => 'required|string'
        ]);

        try {
            // 2. 透過 Sanctum 中間件取得目前發送請求的登入使用者實例
            $user = $request->user();

            // 3. 將 使用者、音檔 與 情境代號 依序傳入 Service 處理
            $result = $this->chatService->processVoiceCommand($user,$request->file('audio'),$request->input('scenario'));

            // 4. 回傳處理結果
            return response()->json($result, 200);

        } catch (Exception $e) {
            // 5. 錯誤處理與日誌紀錄
            Log::error('Voice Chat API Error: ' . $e->getMessage());

            return response()->json([
                'error' => '伺服器處理失敗',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
