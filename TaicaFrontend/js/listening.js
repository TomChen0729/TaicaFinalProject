/**
 * 聽力訓練大廳專屬邏輯 (解耦獨立版)
 */
document.addEventListener('DOMContentLoaded', () => {
    // 獨立維護後端 API 網址
    const apiUrl = 'http://127.0.0.1:82/api';
    const token = localStorage.getItem('auth_token');
    const userName = localStorage.getItem('user_name');
    
    const navRightActions = document.getElementById('navRightActions');
    const btnContainers = document.querySelectorAll('.login-required-btns');

    if (token && userName) {
        // [已登入狀態] 渲染頂部導覽列右側
        navRightActions.innerHTML = `
            <span style="color: #cbd5e1;">${userName} 同學 👋</span>
            <span style="color: #475569;">|</span>
            <a href="dashboard.html" class="nav-link">📊 學習後台</a>
            <button id="logoutBtn" class="btn-logout">登出</button>
        `;

        // 依據結構上的 data-task 屬性，動態渲染對應的關卡進入連結
        btnContainers.forEach(container => {
            const taskId = container.getAttribute('data-task');
            container.innerHTML = `
                <a href="listeningRoom.html?task=${taskId}" class="btn btn-primary btn-enter-pacing">開始聆聽挑戰</a>
            `;
        });

        // 綁定登出點擊事件
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            try {
                await fetch(`${apiUrl}/logout`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
            } catch (e) {
                console.error('聽力大廳登出異常');
            } finally {
                localStorage.clear();
                alert('已安全登出！');
                window.location.href = 'index.html';
            }
        });

    } else {
        // [未登入訪客狀態]
        navRightActions.innerHTML = `
            <span style="color: #94a3b8; font-size: 0.85rem;">🔒 訪客模式</span>
            <a href="auth/login.html" class="btn-login">登入 / 註冊</a>
        `;

        // 鎖定所有關卡按鈕，套用解耦後的灰色鎖定類別
        btnContainers.forEach(container => {
            container.innerHTML = `
                <a href="auth/login.html" class="btn btn-lock-status">請先登入以解鎖關卡</a>
            `;
        });
    }
});