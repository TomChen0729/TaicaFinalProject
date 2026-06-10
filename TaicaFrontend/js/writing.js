/**
 * 導覽列 (Navbar) 專屬狀態邏輯
 */
document.addEventListener('DOMContentLoaded', () => {
    // API 網址設定
    const apiUrl = 'http://127.0.0.1:8000/api';
    const token = localStorage.getItem('auth_token');
    const userName = localStorage.getItem('user_name');
    
    const navRightActions = document.getElementById('navRightActions');

    if (token && userName) {
        // [已登入狀態] 渲染頂部導覽列右側
        navRightActions.innerHTML = `
            <span style="color: #cbd5e1;">${userName} 同學 👋</span>
            <span style="color: #475569;">|</span>
            <a href="dashboard.html" class="nav-link">📊 學習後台</a>
            <button id="logoutBtn" class="btn-logout">登出</button>
        `;

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
                console.error('登出異常:', e);
            } finally {
                // 清除本地儲存並導回首頁
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user_name');
                alert('已安全登出！');
                window.location.href = 'index.html';
            }
        });

    } else {
        // [未登入訪客狀態] 渲染登入按鈕
        navRightActions.innerHTML = `
            <span style="color: #94a3b8; font-size: 0.85rem;">🔒 訪客模式</span>
            <a href="Auth/login.html" class="btn-login">登入 / 註冊</a>
        `;
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const API_BASE = 'http://127.0.0.1:8000/api/writing';
    const token = localStorage.getItem('auth_token');
    
    let currentCategory = ''; 
    let currentImageBase64 = ''; // 儲存生成的圖片

    if (!token) {
        alert('請先登入會員以進行寫作訓練！');
        window.location.href = 'auth/login.html';
        return;
    }

    const essayInput = document.getElementById('essay-input');
    const wordCountDisplay = document.getElementById('word-count');

    // 即時字數統計
    essayInput.addEventListener('input', () => {
        const text = essayInput.value.trim();
        const words = text ? text.split(/\s+/).length : 0;
        wordCountDisplay.textContent = words;
    });

    // 1. 綁定類別選擇卡片 (呼叫生圖 API)
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            // 🌟 關鍵修改：使用 currentTarget 確保不管點到卡片哪裡，都能抓到 data-category
            currentCategory = e.currentTarget.getAttribute('data-category');
            
            // 禁用所有卡片，避免重複點擊
            document.querySelectorAll('.category-btn').forEach(b => {
                b.style.pointerEvents = 'none';
                b.style.opacity = '0.6';
            });

            const loadingDiv = document.getElementById('prompt-loading');
            loadingDiv.textContent = '🎨 AI 畫家正在為您生成專屬情境圖，請稍候約 10-20 秒... ⏳';
            loadingDiv.classList.remove('hidden');

            try {
                const res = await fetch(`${API_BASE}/prompt?category=${currentCategory}`, {
                    method: 'GET',
                    headers: { 
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json' 
                    }
                });
                
                const result = await res.json();
                if (result.success) {
                    const data = result.data;
                    currentImageBase64 = data.image_base64;
                    
                    document.getElementById('prompt-image').src = `data:image/png;base64,${currentImageBase64}`;
                    document.getElementById('prompt-title').textContent = data.title;
                    document.getElementById('prompt-translation').textContent = data.translation;
                    document.getElementById('prompt-wordcount').textContent = data.suggested_word_count;
                    
                    document.getElementById('category-section').classList.add('hidden');
                    document.getElementById('writing-section').classList.remove('hidden');
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error(error);
                alert('獲取圖片失敗，請重試或確認生圖伺服器是否開啟。');
                // 恢復卡片點擊
                document.querySelectorAll('.category-btn').forEach(b => {
                    b.style.pointerEvents = 'auto';
                    b.style.opacity = '1';
                });
            } finally {
                loadingDiv.classList.add('hidden');
            }
        });
    });

    // 2. 提交作文並獲取批改 (傳送 Base64 圖片 + 作文)
    document.getElementById('btn-submit-essay').addEventListener('click', async (e) => {
        e.preventDefault();
        const essayText = essayInput.value.trim();
        if (essayText.length < 20) {
            alert('請至少撰寫幾個完整的句子再提交喔！');
            return;
        }

        const btn = document.getElementById('btn-submit-essay');
        const evalLoading = document.getElementById('eval-loading');
        
        btn.disabled = true;
        btn.classList.add('hidden');
        essayInput.disabled = true;
        evalLoading.classList.remove('hidden');

        try {
            const res = await fetch(`${API_BASE}/evaluate`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    image_base64: currentImageBase64, 
                    essay: essayText,
                    category: currentCategory 
                })
            });
            
            const result = await res.json();
            if (result.success) {
                renderEvaluation(result.data);
                document.getElementById('evaluation-section').classList.remove('hidden');
                document.getElementById('evaluation-section').scrollIntoView({ behavior: 'smooth' });
            } else {
                throw new Error(result.error || '批改失敗');
            }
        } catch (error) {
            console.error(error);
            alert('多模態 AI 批改發生錯誤，請稍後再試。');
            btn.disabled = false;
            btn.classList.remove('hidden');
            essayInput.disabled = false;
        } finally {
            evalLoading.classList.add('hidden');
        }
    });

    // 3. 渲染成績面板 (移除雷達圖，加入防呆機制)
    function renderEvaluation(data) {
        // debugger;
        console.log("🔍 AI 批改原始資料：", data);

        // 建立安全網，防止 data.scores 或 data.feedback 不存在時導致崩潰
        const scores = data.scores || {};
        const feedback = data.feedback || {};

        // 設定總分
        const totalScoreEl = document.getElementById('total-score');
        totalScoreEl.textContent = data.total_score || 0;
        
        // 確保總分的 CSS 樣式能正確覆蓋
        if (data.total_score >= 80) {
            totalScoreEl.className = 'text-6xl font-extrabold text-green-600 mb-2';
        } else if (data.total_score >= 60) {
            totalScoreEl.className = 'text-6xl font-extrabold text-yellow-500 mb-2';
        } else {
            totalScoreEl.className = 'text-6xl font-extrabold text-red-500 mb-2';
        }

        // 將各項子分數填入 HTML 的方格中 (如果沒給分數預設顯示 0)
        document.getElementById('score-content').textContent = scores.relevance || 0;
        document.getElementById('score-org').textContent = scores.organization || 0;
        document.getElementById('score-vocab').textContent = scores.vocabulary || 0;
        document.getElementById('score-grammar').textContent = scores.grammar || 0;

        // 填入詳細回饋 (加上防呆預設文字)
        document.getElementById('fb-content').textContent = feedback.relevance || 'AI 未提供內容建議。';
        document.getElementById('fb-org').textContent = feedback.organization || 'AI 未提供組織建議。';
        document.getElementById('fb-vocab').textContent = feedback.vocabulary || 'AI 未提供詞彙建議。';
        document.getElementById('fb-grammar').textContent = feedback.grammar || 'AI 未提供文法建議。';
        
        // 填入修正範文
        // document.getElementById('revised-essay').textContent = data.revised_essay || 'AI 暫無提供潤飾範文。';
        document.getElementById('btn-restart').addEventListener('click', () => {
            location.reload();
        });
    }
});