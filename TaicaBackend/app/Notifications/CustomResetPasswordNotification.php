<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;
    public $token;
    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        //
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // 你的前端重設密碼連結
        $frontendUrl = "http://127.0.0.1:5500/TaicaFrontend/Auth/resetPwd.html?token={$this->token}&email={$notifiable->email}";

        return (new MailMessage)
            ->subject('🔒 實用英語生存指南 - 密碼重設通知')
            // ★ 關鍵：告訴 Laravel 去 resource/views/emails 找 reset-password.blade.php
            ->view('emails.reset-password', [
                'userName' => $notifiable->name,
                'resetUrl' => $frontendUrl
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
