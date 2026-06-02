<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;
use Exception;
class DashboardController extends Controller
{
    //
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * 取得儀表板基礎統計資料
     */
    public function getDashboard(Request $request)
    {
        try {
            $user = $request->user();
            $data = $this->dashboardService->getUserDashboardData($user);
            return response()->json($data, 200);
        } catch (Exception $e) {
            return response()->json(['message' => '無法取得儀表板資料', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 🔹 新增：取得分頁與分類的完整學習紀錄
     */
    public function getLearningHistory(Request $request)
    {
        try {
            $user = $request->user();

            // 接收前端篩選參數，預設為每頁 5 筆，類別為全部 (all)
            $moduleType = $request->query('module_type', 'all');
            $perPage = 5;

            $history = $this->dashboardService->getPaginatedHistory($user, $perPage, $moduleType);
            return response()->json($history, 200);
        } catch (Exception $e) {
            return response()->json(['message' => '無法取得學習紀錄', 'error' => $e->getMessage()], 500);
        }
    }
}
