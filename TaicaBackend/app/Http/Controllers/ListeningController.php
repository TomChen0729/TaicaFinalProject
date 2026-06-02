<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ListeningService;
use Exception;
class ListeningController extends Controller
{
    protected ListeningService $listeningService;

    public function __construct(ListeningService $listeningService)
    {
        $this->listeningService = $listeningService;
    }

    // 取得指定或隨機聽力題目與生成的語音檔 URL
    public function getTask(Request $request)
    {
        try {
            $taskId = $request->query('task_id');
            $task = $this->listeningService->getTask($taskId);
            return response()->json($task, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // 提交答案並記錄通關狀態
    public function submitAnswer(Request $request)
    {
        $request->validate([
            'task_id' => 'required|string',
            'user_answer' => 'required|string'
        ]);

        $user = $request->user();

        try {
            $result = $this->listeningService->checkAnswerAndRecord(
                $user,
                $request->task_id,
                $request->user_answer
            );
            return response()->json($result, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
