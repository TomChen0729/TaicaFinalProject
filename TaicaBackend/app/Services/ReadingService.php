<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class ReadingService
{
    /**
     * 呼叫 AI 生成繁體中文重點整理
     */
    public function generateSummaryWithAI($text)
    {
        $prompt = "請閱讀以下英文文章，並以「繁體中文」列出 3 到 5 點的重點整理。請直接輸出文字，使用條列式（- ），不要包含任何多餘的開場白或 JSON 格式。\n\n文章內容：\n" . $text;

        try {
            $response = Http::timeout(120)->post('http://127.0.0.1:11434/api/chat', [
                'model' => 'llama3.2:3b',
                'messages' => [
                    ['role' => 'system', 'content' => '你是一位專業的英文老師，擅長抓出文章重點並翻譯成通順的台灣繁體中文。'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'stream' => false,
                // 注意：這裡是生成純文字重點，所以不需要 format => 'json'
            ]);

            if ($response->successful()) {
                return $response->json('message.content');
            }
            throw new \Exception("Ollama API 呼叫失敗");
        } catch (\Exception $e) {
            throw new \Exception("無法連線到 AI 模型：" . $e->getMessage());
        }
    }

    /**
     * 呼叫 AI 生成選擇題 (JSON 格式)
     */
    public function generateSimpleQuizWithAI($text)
    {
        // 1. 強化 Prompt：加上強烈警告，並明確指示用 [ 開頭
        $prompt = "
            請根據以下文章，生成 3 題閱讀測驗選擇題。
            ⚠️ 嚴格限制：你必須回傳一個 JSON 陣列 (Array)，必須以 `[` 開頭，並以 `]` 結尾。
            
            語言要求：
            1. 題目 (question) 必須是純英文。
            2. 4 個選項 (options) 必須是純英文的實際內容。
            3. 答案 (correct_answer) 必須是完整的英文選項。
            4. 詳解 (explanation) 必須是台灣繁體中文。
            
            格式範例 (僅供 JSON 結構參考，請填入你自己根據文章想出來的內容)：
            [
              { 
                \"question\": \"Based on the article, what is... (在這裡寫出你的英文問題)\", 
                \"options\": [\"第一選項的真實英文文字\", \"第二選項的真實英文文字\", \"第三選項的真實英文文字\", \"第四選項的真實英文文字\"], 
                \"correct_answer\": \"正確的那個選項的完整文字\", 
                \"explanation\": \"繁體中文解析寫在這裡...\" 
              }
            ]
            
            文章內容：\n" . $text;

        try {
            $response = Http::timeout(120)->post('http://127.0.0.1:11434/api/chat', [
                'model' => 'qwen3-coder:480b-cloud',
                'messages' => [
                    ['role' => 'system', 'content' => '你只能輸出純 JSON 陣列資料，絕對不要輸出任何 Markdown 符號。'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'stream' => false,
                'format' => 'json'
            ]);

            if ($response->successful()) {
                $content = $response->json('message.content');
                $decoded = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    
                    // 2. 🌟 後端防呆機制：如果 AI 忘記加陣列 []，我們手動幫它包起來
                    // 判斷方式：如果解析出來的陣列有 'question' 這個 key，代表它是一個單一物件
                    if (isset($decoded['question'])) {
                        $decoded = [$decoded]; 
                    }

                    return $decoded;
                } else {
                    throw new \Exception("模型輸出的格式不是有效的 JSON。");
                }
            }
            throw new \Exception("Ollama API 呼叫失敗");
        } catch (\Exception $e) {
            throw new \Exception("無法連線到 AI 模型：" . $e->getMessage());
        }
    }
}