<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Carbon\Carbon;

class DashboardService
{
    /**
     * 取得使用者的儀表板統計數據（不含歷史紀錄，維持極速載入）
     */
    public function getUserDashboardData(User $user): array
    {
        // 撈出該使用者的所有對話紀錄以計算基本指標
        $conversations = Conversation::where('user_id', $user->id)->get();

        $totalConversations = $conversations->count();
        $successCount = $conversations->where('is_success', true)->count();

        // 產出近七日分模組圖表數據
        $chartData = $this->getWeeklyChartData($user->id);

        return [
            'total_conversations' => $totalConversations,
            'success_count' => $successCount,
            'chart_labels' => $chartData['labels'],
            'chart_speak_values' => $chartData['speak_values'],   // 拆分口說數據
            'chart_listen_values' => $chartData['listen_values'], // 拆分聽力數據
            'unlocked_cards' => $this->getUnlockedKnowledgeCards($user->id),
        ];
    }

    /**
     * 核心新增：動態資料庫分頁與篩選機制
     */
    public function getPaginatedHistory(User $user, int $perPage, string $moduleType): array
    {
        $query = Conversation::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // 如果有指定特定學習類別，則加入篩選條件
        if ($moduleType !== 'all') {
            $query->where('module_type', $moduleType);
        }

        // 呼叫 Laravel 內建分頁器
        $paginator = $query->paginate($perPage);

        // 格式化當前分頁頁面的資料內容
        $formattedData = collect($paginator->items())->map(function ($conv) {
            return [
                'scenario' => $this->formatScenarioName($conv->scenario_id, $conv->module_type ?? 'speak'),
                'module_type' => $conv->module_type ?? 'speak',
                'user_text' => $conv->user_text,
                'ai_reply' => $conv->ai_reply,
                'suggestion' => $conv->suggestion,
                'is_success' => (bool) $conv->is_success,
                'date' => $conv->created_at->format('Y-m-d H:i')
            ];
        })->toArray();

        // 包裝成符合前端非同步調用的標準分頁 JSON 結構
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'data' => $formattedData
        ];
    }

    /**
     * 建立近七日分流練習量統計
     */
    private function getWeeklyChartData(int $userId): array
    {
        $labels = [];
        $speakValues = [];
        $listenValues = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('m/d');

            // 統計該日「口說」練習次數
            $speakCount = Conversation::where('user_id', $userId)
                ->where('module_type', 'speak')
                ->whereDate('created_at', $date->toDateString())
                ->count();
            $speakValues[] = $speakCount;

            // 統計該日「聽力」練習次數
            $listenCount = Conversation::where('user_id', $userId)
                ->where('module_type', 'listen')
                ->whereDate('created_at', $date->toDateString())
                ->count();
            $listenValues[] = $listenCount;
        }

        return [
            'labels' => $labels,
            'speak_values' => $speakValues,
            'listen_values' => $listenValues
        ];
    }

    /**
     * 獲取遊戲化知識卡狀態
     */
    private function getUnlockedKnowledgeCards(int $userId): array
    {
        $clearedScenarios = Conversation::where('user_id', $userId)
            ->where('is_success', true)
            ->pluck('scenario_id')
            ->unique()
            ->toArray();

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

    /**
     * 智慧情境名稱對照處理
     */
    private function formatScenarioName(string $scenarioId, string $moduleType = 'speak'): string
    {
        $speakNames = [
            'fast_food' => '🍔 口說：速食店點餐',
            'supermarket' => '🛒 口說：超市結帳',
            'directions' => '🗺️ 口說：街頭問路',
            'immigration' => '🛂 口說：海關入境'
        ];

        $listenNames = [
            'fast_food_pickup' => '🍔 聽力：速食店取餐廣播',
            'train_station' => '🚆 聽力：車站月台廣播'
        ];

        if (array_key_exists($scenarioId, $listenNames)) {
            return $listenNames[$scenarioId];
        }

        return $speakNames[$scenarioId] ?? $scenarioId;
    }
}
