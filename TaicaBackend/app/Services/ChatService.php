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
     * 處理語音指令的完整主流程（已升級：支援會員識別、全參數化資料庫管理與道地建議解析）
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
                'is_success' => false
            ];
        }

        // 2. 透過私有方法，直接去資料庫撈取當前關卡的 System Prompt
        $systemPrompt = $this->getSystemPromptByScenario($scenarioId);

        // 3. 將文字與動態 Prompt 送給 LLM 產生回覆 (維持使用地端 Ollama)
        $aiReply = $this->generateReply($userText, $systemPrompt);

        // 4. 任務條件與導師建議解析
        $isSuccess = false;
        $suggestion = null;

        // 檢查地端 Gemma2 的回覆中是否包含 [SUCCESS] 標籤
        if (strpos($aiReply, '[SUCCESS]') !== false) {
            $isSuccess = true;
            // 剔除標籤
            $aiReply = str_replace('[SUCCESS]', '', $aiReply);
        }

        // 檢查回覆中是否包含 [SUGGESTION] 標籤
        if (strpos($aiReply, '[SUGGESTION]') !== false) {
            $parts = explode('[SUGGESTION]', $aiReply);
            $aiReply = $parts[0]; // 前半段保留為給前端發音的對話
            $suggestion = trim($parts[1] ?? ''); // 後半段獨立抽出來作為導師建議
        }

        // 清理多餘的空白字元
        $aiReply = trim($aiReply);

        // 5. 執行核心資料庫記錄，將對話、建議及通關結果綁定該會員帳號
        Conversation::create([
            'user_id' => $user->id,
            'scenario_id' => $scenarioId,
            'user_text' => $userText,
            'ai_reply' => $aiReply,
            'suggestion' => $suggestion, // 寫入導師建議
            'is_success' => $isSuccess
        ]);

        // 6. 回傳最終結構給 Controller
        return [
            'user_text' => $userText,
            'ai_reply' => $aiReply,
            'is_success' => $isSuccess
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
     * 呼叫地端 Ollama 產生對話回覆
     */
    private function generateReply(string $userText, string $systemPrompt): string
    {
        // 🔹 改動：拿掉結尾的 \nCashier:，改用明確的指令分隔符號，並再次強烈提醒它輸出標籤
        $fullPrompt = "{$systemPrompt}\n\n"
                    . "Customer says: \"{$userText}\"\n\n"
                    . "Your response (Must include [SUCCESS] and [SUGGESTION] tags if applicable):";

        $response = Http::timeout(30)->post('http://127.0.0.1:11434/api/generate', [
            'model' => 'gemma2:2b',
            'prompt' => $fullPrompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.6
            ]
        ]);

        if ($response->failed()) {
            throw new Exception('Ollama API 錯誤: ' . $response->body());
        }

        return trim($response->json('response'));
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
