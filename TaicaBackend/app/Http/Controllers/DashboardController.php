<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;
class DashboardController extends Controller
{
    //
    protected DashboardService $dashboardService;

    // 依賴注入 DashboardService
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    /**
     * 取得儀表板資料的 API 端點
     */
    public function getDashboard(Request $request)
    {
        try {
            // 從 Token 中取得目前登入的使用者實例
            $user = $request->user();

            // 呼叫 Service 取得統計數據
            $data = $this->dashboardService->getUserDashboardData($user);

            // 回傳 200 與 JSON 格式資料
            return response()->json($data, 200);

        } catch (\Exception $e) {
            return response()->json(['message' => '無法取得儀表板資料', 'error' => $e->getMessage()], 500);
        }
    }
}
