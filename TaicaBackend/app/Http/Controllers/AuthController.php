<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Support\Facades\Password;
class AuthController extends Controller
{

    protected AuthService $authService;

    // 依賴注入 AuthService
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * 會員註冊 API
     */
    public function register(Request $request)
    {
        // 1. Controller 負責驗證 Request 資料格式
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed'
        ]);

        // 2. 呼叫 Service 處理註冊與 Token 核發邏輯
        $result = $this->authService->registerUser($fields);

        // 3. Controller 負責回傳 HTTP 狀態與 JSON
        return response()->json([
            'message' => '註冊成功',
            'user' => $result['user'],
            'token' => $result['token']
        ], 201);
    }

    /**
     * 會員登入 API
     */
    public function login(Request $request)
    {
        // 1. 驗證資料格式
        $fields = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        // 2. 呼叫 Service 處理登入邏輯
        $result = $this->authService->loginUser($fields);

        // 3. 處理失敗與成功的回傳結果
        if (!$result) {
            return response()->json([
                'message' => '帳號或密碼錯誤'
            ], 401);
        }

        return response()->json([
            'message' => '登入成功',
            'user' => $result['user'],
            'token' => $result['token']
        ], 200);
    }

    /**
     * 會員登出 API
     */
    public function logout(Request $request)
    {
        // 直接將 Request 中的使用者實例傳遞給 Service 執行銷毀動作
        $this->authService->logoutUser($request->user());

        return response()->json([
            'message' => '已成功登出，Token 已銷毀'
        ], 200);
    }

    /**
     * 忘記密碼：發送重設信件
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = $this->authService->sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => '密碼重設信件已寄出，請檢查您的信箱！'], 200)
            : response()->json(['message' => '找不到該電子郵件對應的帳號。'], 400);
    }

    /**
     * 重設密碼：接收前端新密碼與 Token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = $this->authService->resetPassword($request->only('email', 'password', 'password_confirmation', 'token'));

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => '密碼重設成功！您可以立刻使用新密碼登入了。'], 200)
            : response()->json(['message' => '密碼重設失敗，Token 可能已過期或無效。'], 400);
    }
}
