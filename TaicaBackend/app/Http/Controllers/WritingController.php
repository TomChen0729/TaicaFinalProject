<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WritingService;

class WritingController extends Controller
{
    protected $writingService;

    // 內建的看圖寫作主題任務
    private $imageTasks = [
        'restaurant' => [
            'title' => 'Smart Restaurant Experience',
            'translation' => '智慧餐廳與自動化點餐服務',
            'suggested_word_count' => '100-150 words'
        ],
        'zoo' => [
            'title' => 'Interactive Zoo Tour',
            'translation' => '現代動物園與數位互動導覽',
            'suggested_word_count' => '100-150 words'
        ],
        'airport' => [
            'title' => 'Automated Airport Check-in',
            'translation' => '未來機場與自助報到系統',
            'suggested_word_count' => '100-150 words'
        ]
    ];

    public function __construct(WritingService $writingService)
    {
        $this->writingService = $writingService;
    }

    /**
     * 獲取題目並生成對應圖片
     */
    public function getImagePrompt(Request $request)
    {
        set_time_limit(300); // SD 畫圖需要時間
        $category = $request->input('category');
        
        if (!isset($this->imageTasks[$category])) {
            return response()->json(['error' => '找不到該主題'], 404);
        }

        try {
            // 呼叫 SD 產生圖片
            $base64Image = $this->writingService->generateImageWithSD($category);
            
            $taskData = $this->imageTasks[$category];
            $taskData['image_base64'] = $base64Image;

            return response()->json(['success' => true, 'data' => $taskData]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 接收作文與圖片，進行多模態批改
     */
    public function evaluateEssay(Request $request)
    {
        set_time_limit(300);
        
        $base64Image = $request->input('image_base64');
        $essay = $request->input('essay');
        $category = $request->input('category'); // 前端需要多傳這個

        if (empty($base64Image) || empty($essay)) {
            return response()->json(['error' => '圖片或作文資料不完整'], 400);
        }

        try {
            $evaluation = $this->writingService->evaluateEssayWithImageAI($base64Image, $essay);

            // 儲存至資料庫
            \App\Models\Conversation::create([
                'user_id'     => $request->user()->id,
                'scenario_id' => $category ?? 'restaurant',
                'module_type' => 'write',
                'user_text'   => $essay,
                'ai_reply'    => '總分：' . ($evaluation['total_score'] ?? 0) . ' 分',
                'suggestion'  => implode("\n", array_filter([
                    isset($evaluation['feedback']['relevance'])    ? '📌 內容：' . $evaluation['feedback']['relevance'] : null,
                    isset($evaluation['feedback']['organization']) ? '🏗️ 組織：' . $evaluation['feedback']['organization'] : null,
                    isset($evaluation['feedback']['vocabulary'])   ? '🔤 詞彙：' . $evaluation['feedback']['vocabulary'] : null,
                    isset($evaluation['feedback']['grammar'])      ? '✍️ 文法：' . $evaluation['feedback']['grammar'] : null,
                ])),
                'is_success'  => ($evaluation['total_score'] ?? 0) >= 60,
            ]);

            return response()->json(['success' => true, 'data' => $evaluation]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}