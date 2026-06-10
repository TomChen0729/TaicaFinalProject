<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WritingService
{
    private array $prompts = [
        'restaurant' => 'a futuristic smart restaurant where a robot waiter is serving delicious food to happy customers, bright and modern, photorealistic, highly detailed',
        
        'zoo'        => 'a modern zoo with interactive digital information screens, happy children looking at cute animals, sunny day, photorealistic, highly detailed',
        
        'airport'    => 'a high-tech airport terminal with automated self check-in kiosks and travelers with luggage, wide angle, photorealistic, highly detailed',    
    ];

    /**
     * 生成圖片（專屬 Cloudflare Workers AI 穩定版）
     */
    public function generateImageWithSD($category): string
    {
        $prompt = $this->prompts[$category] ?? 'beautiful landscape, photorealistic, highly detailed';

        try {
            // 直接呼叫 Cloudflare 進行高畫質生圖
            return $this->generateWithCloudflare($prompt);
        } catch (\Exception $e) {
            Log::error('Cloudflare 生圖失敗：' . $e->getMessage());
            throw new \Exception('雲端生圖服務發生錯誤，請確認網路狀態或 API 金鑰設定。');
        }
    }

    /**
     * Cloudflare Workers AI 核心引擎
     * 需在 .env 設定：CF_API_TOKEN、CF_ACCOUNT_ID
     */
    private function generateWithCloudflare(string $prompt): string
    {
        $accountId = env('CF_ACCOUNT_ID');
        $apiToken  = env('CF_API_TOKEN');

        if (!$accountId || !$apiToken) {
            throw new \Exception('Cloudflare 環境變數未設定（CF_ACCOUNT_ID / CF_API_TOKEN）');
        }

        $url = "https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/@cf/stabilityai/stable-diffusion-xl-base-1.0";

        $response = Http::withToken($apiToken)
            ->timeout(60)
            ->post($url, ['prompt' => $prompt]);

        if (!$response->successful()) {
            throw new \Exception('Cloudflare API 錯誤，狀態碼：' . $response->status());
        }

        return base64_encode($response->body());
    }

    /**
     * Llama-Vision 多模態作文批改（llava，本地 Ollama）
     */
    public function evaluateEssayWithImageAI(string $base64Image, string $essay): array
    {
        ini_set('memory_limit', '512M');
        $accountId = env('CF_ACCOUNT_ID');
        $apiToken  = env('CF_API_TOKEN');

        if (!$accountId || !$apiToken) {
            throw new \Exception('Cloudflare 環境變數未設定（CF_ACCOUNT_ID / CF_API_TOKEN）');
        }

        $prompt = <<<PROMPT
            ⚠️ 你必須「只」回傳一個純 JSON 物件，不得有任何 Markdown、標題、條列符號或說明文字。直接從 { 開始，到 } 結束。

            你是一位嚴格的英文檢定考官。請觀察使用者上傳的圖片，並閱讀學生的「看圖寫作」文章。
            
            ⚠️ 絕對規則：
            1. 只能輸出 JSON，不能有任何額外文字、問候語或 Markdown 標記。
            2. JSON 的 Key 必須完全與下方一致，絕對不可更改大小寫。
            3. 請根據學生的真實作文進行評分與回饋，**絕對不可以照抄下方的佔位符號 (Placeholder)**。

            評分標準（每項滿分 25 分，總分 100 分）：
            1. 圖片關聯度 (Relevance)：文章內容是否精確描述圖片中的細節與情境？
            2. 組織結構 (Organization)：段落連貫性與邏輯。
            3. 詞彙運用 (Vocabulary)：單字豐富度與精確度。
            4. 文法正確性 (Grammar)：句型結構與時態。

            請回傳此 JSON 格式：
            {
                "scores": {
                    "relevance": [請填入 0-25 的整數],
                    "organization": [請填入 0-25 的整數],
                    "vocabulary": [請填入 0-25 的整數],
                    "grammar": [請填入 0-25 的整數]
                },
                "total_score": [請填入 0-100 的總分],
                "feedback": {
                    "relevance": "[繁體中文] 引用學生文章的句子，指出哪裡描述得不夠生動，並給出建議。",
                    "organization": "[繁體中文] 引用學生文章的句子，說明邏輯或段落如何改善。",
                    "vocabulary": "[繁體中文] 列出文中 2 個基礎單字，提供高階替換字與例句。",
                    "grammar": "[繁體中文] 引用文中的文法錯誤，寫出正確句型並說明原因。"
                },
                
            }

            學生作文內容：
            {$essay}
        PROMPT;
        
        try {
            // 🌟 將 Base64 轉換為 Cloudflare API 接受的 byte array 格式
            $imageData = base64_decode($base64Image);
            $imageArray = array_values(unpack('C*', $imageData));

            $url = "https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/@cf/meta/llama-3.2-11b-vision-instruct";

            // 呼叫 Cloudflare Llama-Vision 模型
            $response = Http::withToken($apiToken)
                ->timeout(120) // 雲端通常只要 10~30 秒，不用等到 300 秒了
                ->post($url, [
                    'prompt' => $prompt,
                    'image'  => $imageArray,
                    'max_tokens' => 2048,
                ]);

            $rawBody = $response->body();
            Log::debug('CF 原始 body', ['raw' => substr($rawBody, 0, 2000)]);
            $content = $response->json('result.response');
            Log::debug('CF result.response', ['content' => $content]);

            if (!$response->successful()) {
                throw new \Exception('Cloudflare API 錯誤，狀態碼：' . $response->status() . '，原因：' . $response->body());
            }

            // Cloudflare 回傳的 JSON 結構通常在 result.response 裡面
            $content = $response->json('result.response');

            // 🌟 防呆 1：如果 Laravel 已經把它轉成陣列了，直接收下
            if (is_array($content)) {
                return $content;
            }

            if (!is_string($content)) {
                throw new \Exception('Cloudflare 回傳格式異常。');
            }

            // 🌟 防呆 2：精準捕捉 JSON 區塊，砍掉 AI 前後可能講的廢話
            if (preg_match('/\{.*\}/s', $content, $matches)) {
                $content = $matches[0];
            }

            // 🌟 防呆 3：過濾掉會導致 JSON 解析崩潰的隱形控制字元 (Control Characters)
            // 這是專門對付 JSON_ERROR_CTRL_CHAR 的特效藥
            $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $content);

            // 移除可能殘留的 Markdown code fence
            $content = preg_replace('/^```json\s*/i', '', trim($content));
            $content = preg_replace('/```$/', '', trim($content));

            // 🌟 自動補完缺失的括號 (防呆修復)
            $content = trim($content);
            $openBraces = substr_count($content, '{');
            $closeBraces = substr_count($content, '}');
            $openBrackets = substr_count($content, '[');
            $closeBrackets = substr_count($content, ']');

            if ($openBraces > $closeBraces) {
                $content .= str_repeat('}', $openBraces - $closeBraces);
            }
            if ($openBrackets > $closeBrackets) {
                $content .= str_repeat(']', $openBrackets - $closeBrackets);
            }

            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON 解析失敗 (' . json_last_error_msg() . ')，AI 原始輸出：' . $content);
            }

            return $decoded;


        } catch (\Exception $e) {
            Log::error(' 批改失敗：' . $e->getMessage());
            throw new \Exception('無法連線到雲端 AI 考官：' . $e->getMessage());
        }
       
    }
}