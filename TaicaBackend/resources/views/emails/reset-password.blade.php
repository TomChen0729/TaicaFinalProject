<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* 電子郵件專用的基礎 CSS Reset */
        body, html { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; }
        table { border-spacing: 0; border-collapse: collapse; width: 100%; }

        /* 外部灰色背景區塊 */
        .email-wrapper { background-color: #f3f4f6; padding: 40px 20px; width: 100%; }

        /* 置中的白色卡片 */
        .email-card { max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

        /* 頂部紅底標題 */
        .email-header { background-color: #ef4444; color: #ffffff; padding: 25px; text-align: center; font-size: 22px; font-weight: bold; }

        /* 內文區塊 */
        .email-body { padding: 30px; color: #334155; line-height: 1.6; font-size: 16px; }
        .email-body h1 { font-size: 20px; color: #0f172a; margin-top: 0; }

        /* 按鈕容器與樣式 */
        .btn-wrapper { text-align: center; margin: 30px 0; }
        .btn { display: inline-block; background-color: #ef4444; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; font-size: 16px; }

        /* 底部備用網址與版權列 */
        .email-footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; }
        .raw-link { word-break: break-all; color: #3b82f6; text-decoration: none; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-card">

            <div class="email-header">
                日常英語生存對話 🗣️
            </div>

            <div class="email-body">
                <h1>Hello, {{ $userName }} 同學！</h1>
                <p>我們收到了您要求重設密碼的申請。如果您準備好重拾英語口說練習，請點擊下方的按鈕設定全新密碼：</p>

                <div class="btn-wrapper">
                    <a href="{{ $resetUrl }}" class="btn">設定新密碼</a>
                </div>

                <p>此連結將在 <strong>60 分鐘後失效</strong>。如果您並沒有提出此申請，請直接忽略這封信件，您的帳號將維持安全狀態。</p>

                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 25px 0;">

                <p style="font-size: 14px; color: #64748b;">
                    如果上方的按鈕無法點擊，請複製以下網址並貼上至瀏覽器：<br>
                    <a href="{{ $resetUrl }}" class="raw-link">{{ $resetUrl }}</a>
                </p>
            </div>

            <div class="email-footer">
                © {{ date('Y') }} 實用英語生存指南. All rights reserved.
            </div>

        </div>
    </div>
</body>
</html>
