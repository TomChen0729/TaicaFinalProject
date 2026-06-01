<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Scenario;
use Carbon\Carbon;

class DashboardService
{
    /**
     * 取得使用者的儀表板完整統計數據（含圖表與知識卡）
     */
    public function getUserDashboardData(User $user): array
    {
        // 1. 撈出該使用者的所有對話紀錄
        $conversations = Conversation::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalConversations = $conversations->count();
        $successCount = $conversations->where('is_success', true)->count();

        // 2. 產出近七日圖表數據 (統計最近 7 天，每天的練習次數)
        $chartData = $this->getWeeklyChartData($user->id);

        // 3. 獲取遊戲化知識卡狀態 (根據通過的情境，解鎖對應的核心句型卡)
        $unlockedCards = $this->getUnlockedKnowledgeCards($user->id);

        // 4. 整理最近的歷史紀錄 (取最新 5 筆即可，保持畫面精簡)
        $recentHistory = $conversations->take(5)->map(function ($conv) {
            return [
                'scenario' => $this->formatScenarioName($conv->scenario_id),
                'user_text' => $conv->user_text,
                'ai_reply' => $conv->ai_reply,
                'suggestion' => $conv->suggestion, // 🔹 修正：將資料庫的導師建議欄位封裝進回傳陣列
                'is_success' => (bool) $conv->is_success,
                'date' => $conv->created_at->format('Y-m-d H:i')
            ];
        })->values()->toArray();

        return [
            'total_conversations' => $totalConversations,
            'success_count' => $successCount,
            'chart_labels' => $chartData['labels'],
            'chart_values' => $chartData['values'],
            'unlocked_cards' => $unlockedCards,
            'recent_history' => $recentHistory,
        ];
    }

    /**
     * 建立近七日練習量統計
     */
    private function getWeeklyChartData(int $userId): array
    {
        $labels = [];
        $values = [];

        // 迴圈跑過去 7 天
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('m/d');

            // 統計該使用者在該日期的對話次數
            $count = Conversation::where('user_id', $userId)
                ->whereDate('created_at', $date->toDateString())
                ->count();
            $values[] = $count;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * 依據通關成功紀錄，動態解鎖實用英語知識卡
     */
    private function getUnlockedKnowledgeCards(int $userId): array
    {
        // 找出此會員所有成功通關的情境 ID (不重複)
        $clearedScenarios = Conversation::where('user_id', $userId)
            ->where('is_success', true)
            ->pluck('scenario_id')
            ->unique()
            ->toArray();

        // 預設全系統的知識卡資料庫庫存
        $allCards = [
            'fast_food' => [
                'title' => '🍟 速食店點餐達人',
                'phrase' => '“Make it a combo, please.”',
                'description' => '百搭升級字組！在任何美式速食店，只要說這句就能將單點輕鬆升級為「主餐+薯條+飲料」的套餐。',
                'bonus' => '已解鎖：點餐關卡'
            ],
            'supermarket' => [
                'title' => '🛒 超市省話結帳王',
                'phrase' => '“Keep the receipt, thanks.”',
                'description' => '當不需要發票或收據時，直接瀟灑地說這句，店員就會幫忙處理，省去多餘的對話。',
                'bonus' => '已解鎖：結帳關卡'
            ],
            'directions' => [
                'title' => '🗺️ 街道導航大師',
                'phrase' => '“Is it within walking distance?”',
                'description' => '向路人問路拿到方向後，用這句確認「走路到得了嗎？」，能有效避免誤入走不到的遠方。',
                'bonus' => '已解鎖：問路關卡'
            ]
        ];

        $result = [];
        foreach ($allCards as $key => $card) {
            // 判定是否通關，未通關則進行遮蔽隱藏
            $isUnlocked = in_array($key, $clearedScenarios);
            $result[] = [
                'title' => $card['title'],
                'phrase' => $isUnlocked ? $card['phrase'] : '🔒 尚未解鎖',
                'description' => $isUnlocked ? $card['description'] : '成功通過該情境任務，即可解鎖達人核心核心句型卡。',
                'bonus' => $card['bonus'],
                'is_unlocked' => $isUnlocked
            ];
        }

        return $result;
    }

    private function formatScenarioName(string $scenarioId): string
    {
        $names = [
            'fast_food' => '🍔 速食店點餐',
            'supermarket' => '🛒 超市結帳',
            'directions' => '🗺️ 街頭問路',
            'immigration' => '🛂 海關入境'
        ];
        return $names[$scenarioId] ?? $scenarioId;
    }
}
