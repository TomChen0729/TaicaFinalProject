<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use App\Models\Scenario;
use App\Models\Conversation;
use App\Models\User;
use Exception;

class ChatService
{
    /**
     * 處理語音指令的完整主流程（已升級：支援發音與表達糾正功能）
     */
    public function processVoiceCommand(User $user, UploadedFile $audioFile, string $scenarioId): array
    {
        // 1. 將語音轉為文字 (維持使用 Groq Whisper API)
        $userText = $this->transcribeAudio($audioFile);

        // 如果只錄到雜音
        if (empty(trim($userText))) {
            return [
                'user_text' => '(聽不清楚)',
                'ai_reply' => 'I could not hear clearly. Could you repeat that?',
                'suggestion' => '請試著靠近麥克風再說一次。',
                'pronunciation_fix' => '', // 保持防呆結構一致
                'is_success' => false
            ];
        }

        // 2. 透過私有方法，直接去資料庫撈取當前關卡的 System Prompt
        $systemPrompt = $this->getSystemPromptByScenario($scenarioId);

        // 3. 將文字與動態 Prompt 送給 LLM 產生回覆 (此時 $llmResult 是一個陣列)
        $llmResult = $this->generateReply($userText, $systemPrompt);

        // 4. 任務條件與導師建議解析 (直接從 JSON 解析出的陣列取值，並賦予預設值防呆)
        $aiReply = $llmResult['ai_reply'] ?? 'Got it. Let us continue.';
        $isSuccess = $llmResult['is_success'] ?? false;
        $suggestion = $llmResult['suggestion'] ?? null;
        
        // 🚀 核心新增：精準接住地端 LLM 依據全新 System Prompt 吐出的糾正欄位
        $pronunciationFix = $llmResult['pronunciation_fix'] ?? ''; 

        // 5. 執行核心資料庫記錄，將對話、建議及通關結果綁定該會員帳號
        Conversation::create([
            'user_id' => $user->id,
            'scenario_id' => $scenarioId,
            'user_text' => $userText,
            'ai_reply' => $aiReply,
            'suggestion' => $suggestion, // 寫入導師建議
            'is_success' => $isSuccess,
            
            // ⚠️ 欄位安全提醒：
            // 如果您的 conversations 資料表尚未執行過 Migration 新增 'pronunciation_fix' 欄位，
            // 請先維持下方這行的註解狀態（直接略過不儲存），這樣才不會引發 SQL Unknown Column 報錯。
            // 'pronunciation_fix' => $pronunciationFix 
        ]);

        // 6. 回傳最終結構給 Controller / 前端 (完成前後端資料流解耦對接)
        return [
            'user_text' => $userText,
            'ai_reply' => $aiReply,
            'suggestion' => $suggestion, 
            'is_success' => $isSuccess,
            'pronunciation_fix' => $pronunciationFix // 🚀 核心新增：回傳給前端 index.js 進行動態嵌入
        ];
    }

    /**
     * 呼叫 Groq Whisper API 進行語音轉文字
     */
    private function transcribeAudio(UploadedFile $audioFile): string
    {
        $response = Http::timeout(10)->withHeaders([
            'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
        ])
        ->attach(
            'file', file_get_contents($audioFile->getRealPath()), 'audio.webm'
        )
        ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
            'model' => 'whisper-large-v3',
            'response_format' => 'json',
            'language' => 'en',
        ]);

        if ($response->failed()) {
            throw new Exception('Whisper API 錯誤: ' . $response->body());
        }

        return $response->json('text');
    }

    /**
     * 呼叫地端 Ollama 產生對話回覆與評估
     */
    private function generateReply(string $userText, string $systemPrompt): array
    {
        $fullPrompt = "{$systemPrompt}\n\n"
                    . "Student says: \"{$userText}\"\n\n"
                    . "Please strictly output your evaluation in JSON format.";

        $response = Http::timeout(120)->post('http://127.0.0.1:11434/api/generate', [
            'model' => 'llama3.1:8b',
            'prompt' => $fullPrompt,
            'format' => 'json', // 強制 Ollama 回傳 JSON 結構
            'stream' => false,
            'options' => [
                'temperature' => 0.2, // 降低溫度，確保評分標準一致與格式穩定
                'num_ctx' => 2048     // 限制上下文長度，保護 VRAM 不溢出
            ]
        ]);

        if ($response->failed()) {
            throw new Exception('Ollama API 連線錯誤: ' . $response->body());
        }

        $responseText = trim($response->json('response'));
        
        // 將 LLM 回傳的 JSON 字串直接轉為 PHP 陣列
        $result = json_decode($responseText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('LLM 回傳的不是有效的 JSON 格式: ' . $responseText);
        }

        return $result;
    }

    /**
     * 從資料庫動態獲取 AI 系統提示詞
     */
    private function getSystemPromptByScenario(string $scenarioId): string
    {
        $scenario = Scenario::find($scenarioId);

        if ($scenario) {
            return $scenario->system_prompt;
        }

        return "You are a helpful assistant. Reply in 1 or 2 short, simple English sentences.";
    }
}