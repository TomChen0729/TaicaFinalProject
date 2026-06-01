<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
class AuthService{
    /**
     * 處理使用者註冊邏輯
     */
    public function registerUser(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * 處理使用者登入邏輯
     * 登入成功回傳陣列，失敗回傳 null
     */
    public function loginUser(array $credentials): ?array
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        $user = User::where('email', $credentials['email'])->first();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * 處理使用者登出邏輯 (銷毀當前 Token)
     */
    public function logoutUser(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
    /**
     * 發送忘記密碼信件
     */
    public function sendResetLink(array $data): string
    {
        // Password::sendResetLink 會自動去 users 表找信箱，並觸發我們自訂的 Notification
        return Password::sendResetLink($data);
    }

    /**
     * 執行密碼重設
     */
    public function resetPassword(array $data): string
    {
        return Password::reset($data, function ($user, $password) {
            $user->password = Hash::make($password);
            $user->save();
        });
    }
 }
