<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ListeningTask;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ListeningService
{
    /**
     * 獲取題目資料 (支援指定 ID 或隨機)
     */
    public function getTask(string $taskId = null): array
    {
        if ($taskId) {
            $task = ListeningTask::find($taskId);
            if (!$task) {
                throw new \Exception('找不到指定的聽力題目。');
            }
        } else {
            $task = ListeningTask::inRandomOrder()->first();
            if (!$task) {
                throw new \Exception('資料庫中目前沒有聽力題目。');
            }
        }

        $audioUrl = $this->generateEdgeTTS($task->script, $task->id);

        return [
            'task_id' => $task->id,
            'title' => $task->title,
            'question' => $task->question,
            'options' => $task->options,
            'audio_url' => $audioUrl
        ];
    }

    /**
     * 驗證答案並寫入紀錄
     */
    public function checkAnswerAndRecord(User $user, string $taskId, string $userAnswer): array
    {
        $task = ListeningTask::findOrFail($taskId);
        $isSuccess = ($task->correct_answer === strtoupper($userAnswer));

        Conversation::create([
            'user_id' => $user->id,
            'scenario_id' => $task->id,
            'module_type' => 'listen',
            'user_text' => '選擇了選項：' . $userAnswer,
            'ai_reply' => '正確解答應為：' . $task->correct_answer,
            'suggestion' => $task->suggestion,
            'is_success' => $isSuccess
        ]);

        return [
            'is_success' => $isSuccess,
            'correct_answer' => $task->correct_answer,
            'suggestion' => $task->suggestion
        ];
    }

    /**
     * 實作語音生成 (免軟連結、自動忽略 SSL 憑證檢查版)
     */
    private function generateEdgeTTS(string $text, string $fileName): string
    {
        // 🔹 改用 public_path 直接定位到專案根目錄的 public/tts 資料夾，徹底不依賴 storage:link
        $publicTtsFolder = public_path('tts');
        $filePath = $publicTtsFolder . '/' . $fileName . '.mp3';

        // 確保公用實體資料夾存在
        if (!file_exists($publicTtsFolder)) {
            mkdir($publicTtsFolder, 0777, true);
        }

        // 如果實體音檔不存在，後端代為向外部 API 請求
        if (!file_exists($filePath)) {
            $encodedText = urlencode($text);
            $tempTtsUrl = "https://translate.google.com/translate_tts?ie=UTF-8&tl=en&client=tw-ob&q={$encodedText}";

            try {
                // 🔹 改用 Laravel Http 門面，並關閉 verify 檢查，繞過本地無 SSL 憑證的錯誤
                $response = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])->get($tempTtsUrl);

                if ($response->successful()) {
                    // 寫入實體二進位音訊檔案到 public/tts/
                    file_put_contents($filePath, $response->body());
                } else {
                    \Log::error("TTS 下載失敗，後端回應狀態碼: " . $response->status());
                    return $tempTtsUrl; // 遠端下載失敗時退回備用外部網址
                }
            } catch (\Exception $e) {
                \Log::error("TTS 後端下載發生嚴重異常: " . $e->getMessage());
                return $tempTtsUrl;
            }
        }

        // 🔹 網址直接對應靜態 public/tts/ 路由，百分之百不會被伺服器權限鎖住
        return url("tts/{$fileName}.mp3");
    }
}
