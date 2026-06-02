<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScenarioController;
use App\Http\Controllers\ListeningController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ==========================================
// 公開路由 (不需登入即可呼叫)
// ==========================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/scenarios/{id}', [ScenarioController::class, 'show']);

// ==========================================
// 受保護路由 (必須在 Header 夾帶 Bearer Token)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    // 舊有的語音對話 API (納入保護，確保登入後才能使用)
    Route::post('/chat', [ChatController::class, 'handleVoiceChat']);
    // 會員登出 API
    Route::post('/logout', [AuthController::class, 'logout']);
    // ★ 新增：取得儀表板資料的 API
    Route::get('/dashboard', [DashboardController::class, 'getDashboard']);
    Route::get('/learning-history', [DashboardController::class, 'getLearningHistory']); // 🔹 新增這行
    // 聽力模組專用 API
    Route::get('/listening/task', [ListeningController::class, 'getTask']);
    Route::post('/listening/submit', [ListeningController::class, 'submitAnswer']);
});
