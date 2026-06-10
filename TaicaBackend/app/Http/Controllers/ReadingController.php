<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReadingService;
use Illuminate\Support\Facades\Validator;
use App\Models\Conversation;
use Illuminate\Support\Str;

class ReadingController extends Controller
{
    protected $readingService;

    // 模擬的內建文章資料庫
    private $articles = [
        1 => [
            'id' => 1,
            'title' => 'The Future of Artificial Intelligence',
            'translate' => '人工智慧的未來',
            'level' => '🟢 入門',
            'content' => 'Artificial intelligence (AI) is rapidly evolving. Machine learning algorithms are now capable of performing complex tasks such as language translation, image recognition, and even creative writing. However, this rapid advancement brings ethical concerns regarding privacy, job displacement, and algorithmic bias. Society must find a balance between technological progress and human-centric policies.'
        ],
        2 => [
            'id' => 2,
            'title' => 'Healthy Habits for Productivity',
            'translate' => '提升生產力的健康習慣',
            'level' => '🟢 入門',
            'content' => 'Maintaining productivity is not just about time management; it heavily relies on physical and mental well-being. Regular exercise, a balanced diet, and adequate sleep form the foundation of a sharp mind. Additionally, taking short breaks using techniques like the Pomodoro method can significantly reduce burnout and improve focus during work hours.'
        ],
        3 => [
            'id' => 3,
            'title' => 'Exploring Deep Space',
            'translate' => '探索深空',
            'level' => '🟡 進階',
            'content' => 'Space exploration has entered a new era with the launch of the James Webb Space Telescope. Unlike its predecessor, Hubble, Webb observes the universe primarily in the infrared spectrum. This allows astronomers to peer through cosmic dust clouds and observe the formation of the first galaxies, potentially answering fundamental questions about the origins of our universe.'
        ],
        4 => [
            'id' => 4,
            'title' => 'The Rise of Passive Investing and ETFs',
            'translate' => '被動投資與 ETF 的崛起',
            'level' => '🟡 進階',
            'content' => 'In recent years, exchange-traded funds (ETFs) have transformed the landscape of personal finance. Unlike actively managed funds, which rely on stock-picking by professionals, most ETFs passively track a specific market index. This approach typically offers lower fees and broader diversification, making it an attractive option for both novice and experienced investors seeking long-term wealth accumulation and stable dividend yields. As financial literacy increases globally, passive investing continues to gain immense popularity.'
        ]
    ];

    public function __construct(ReadingService $readingService)
    {
        $this->readingService = $readingService;
    }

    public function getArticles()
    {
        // 為了讓前端能秒開文章，我們直接回傳完整的文章資料 (包含 content)
        return response()->json(['success' => true, 'data' => array_values($this->articles)]);
    }

    public function generateSummary(Request $request)
    {
        set_time_limit(180);
        $articleId = $request->input('article_id');

        if (!isset($this->articles[$articleId])) {
            return response()->json(['error' => '文章不存在'], 404);
        }

        try {
            $content = $this->articles[$articleId]['content'];
            $summary = $this->readingService->generateSummaryWithAI($content);

            return response()->json([
                'success' => true,
                'data' => [
                    'article' => $this->articles[$articleId],
                    'summary' => $summary
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function generateQuiz(Request $request)
    {
        set_time_limit(180);
        $articleId = $request->input('article_id');

        if (!isset($this->articles[$articleId])) {
            return response()->json(['error' => '文章不存在'], 404);
        }

        try {
            $content = $this->articles[$articleId]['content'];
            // 簡化版：只生成 3 題選擇題
            $quiz = $this->readingService->generateSimpleQuizWithAI($content);

            return response()->json([
                'success' => true,
                'data' => $quiz
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function saveHistory(Request $request)
    {
        try {
            $user = $request->user();
            $records = $request->input('records'); // 接收前端傳來的陣列

            // 確保有收到陣列資料
            if (!is_array($records)) {
                return response()->json(['success' => false, 'error' => '資料格式錯誤'], 400);
            }

            // 使用迴圈將每一題的紀錄存入資料庫
            foreach ($records as $record) {
                Conversation::create([
                    'user_id'     => $user->id,
                    'module_type' => 'read',
                    
                    // 🌟 scenario_id 存題目 (通常資料庫這個欄位長度是 255，使用 Str::limit 防止題目太長報錯)
                    'scenario_id' => Str::limit($record['question'], 250), 
                    
                    // 🌟 user_text 存使用者選擇的選項
                    'user_text'   => $record['user_choice'],
                    
                    // 🌟 ai_reply 存 Correct Answer
                    'ai_reply'    => $record['correct_answer'],
                    
                    // 🌟 suggestion 存 AI 解析
                    'suggestion'  => $record['explanation'],
                    
                    // 🌟 is_success 存這題是否答對
                    'is_success'  => $record['is_correct']
                ]);
            }

            return response()->json(['success' => true], 200);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}