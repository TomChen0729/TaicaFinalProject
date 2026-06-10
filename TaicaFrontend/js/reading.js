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
document.addEventListener('DOMContentLoaded', async () => {
    const API_BASE = 'http://127.0.0.1:8000/api/reading';
    const token = localStorage.getItem('auth_token');
    let currentQuizData = null;
    let articlesList = []; // 儲存所有文章資料

    // 1. 初始化：載入文章列表
    try {
        const res = await fetch(`${API_BASE}/articles`, {
            method: 'GET',
            headers: { 
                'Authorization': `Bearer ${token}`,  // 🌟 帶上 Token
                'Accept': 'application/json'         // 🌟 告訴 Laravel 我們要 JSON，不要亂導向
            }
        });

        const result = await res.json();
        articlesList = result.data;
        
        const container = document.getElementById('article-container');
        
        // 設定幾個不同的 Emoji 給文章輪替使用，讓畫面更活潑
        const emojis = ['📚', '🌍', '🚀', '💡'];

        articlesList.forEach((article, index) => {
            // 建立卡片外層 div
            const card = document.createElement('div');
            card.className = 'card';
            
            // 點擊整張卡片就開啟文章
            card.onclick = () => openArticle(article.id);
            
            // 分配一個 Emoji
            const emoji = emojis[index % emojis.length];

            let badgeClass = 'badge-reading'; // 預設值
            
            if (article.level.includes('入門')) {
                badgeClass = 'badge-easy';
            } else if (article.level.includes('進階')) {
                badgeClass = 'badge-medium';
            } 
            // 填入您提供的卡片 HTML 結構
            card.innerHTML = `
                <div class="card-badge ${badgeClass}">${article.level}</div>
                <div class="emoji">${emoji}</div>
                <div class="scenario-title">${article.title}</div>
                <div class="scenario-desc">${article.translate}</div>
                <div class="action-buttons w-full">
                    <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-full transition">
                        開始閱讀
                    </button>
                </div>
            `;
            
            container.appendChild(card);
        });
    } catch (e) {
        alert('無法載入文章列表');
    }

    // 2. 開啟文章 (秒開，不等待 AI)
    function openArticle(id) {
        // 從剛才抓下來的列表中找出這篇文章
        const article = articlesList.find(a => a.id === id);
        
        // 瞬間填入標題與內文
        document.getElementById('article-title').textContent = article.title;
        document.getElementById('article-content').textContent = article.content;
        
        // 重置 AI 狀態 UI
        document.getElementById('summary-status').textContent = "AI 思考中 ⏳";
        document.getElementById('summary-status').classList.add('animate-pulse');
        document.getElementById('ai-summary').textContent = "正在為您萃取文章精華...";
        
        const quizBtn = document.getElementById('btn-show-quiz');
        quizBtn.disabled = true;
        quizBtn.className = "w-full bg-gray-400 text-white font-bold py-3 px-4 rounded cursor-not-allowed transition duration-300";
        quizBtn.textContent = "AI 正在為您客製化出題中... ⏳";
        
        // 切換畫面 (隱藏列表，顯示閱讀區)
        document.getElementById('article-list-section').classList.add('hidden');
        document.getElementById('reading-section').classList.remove('hidden');
        document.getElementById('quiz-section').classList.add('hidden');

        // 🚀 關鍵：在背景發送 AI 請求 (不使用 await，讓它們在背景慢慢跑)
        fetchAiSummaryBackground(id);
        fetchAiQuizBackground(id);
    }

    // 3. 背景抓取 AI 重點整理
    async function fetchAiSummaryBackground(id) {
        try {
            const res = await fetch(`${API_BASE}/summary`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json' ,
                    'Authorization': `Bearer ${token}`,  // 🌟 帶上 Token
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ article_id: id })
            });
            const result = await res.json();
            
            // AI 處理完畢，更新折疊面板狀態
            document.getElementById('ai-summary').textContent = result.data.summary;
            const statusSpan = document.getElementById('summary-status');
            statusSpan.textContent = "✅ 整理完成";
            statusSpan.classList.remove('animate-pulse');
            statusSpan.classList.add('text-green-600');
            
        } catch (e) {
            document.getElementById('summary-status').textContent = "❌ 整理失敗";
            document.getElementById('ai-summary').textContent = "無法取得重點整理。";
        }
    }

    // 4. 背景抓取 AI 閱讀測驗
    async function fetchAiQuizBackground(id) {
        try {
            const res = await fetch(`${API_BASE}/quiz`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json' ,
                    'Authorization': `Bearer ${token}`,  // 🌟 帶上 Token
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ article_id: id })
            });
            const result = await res.json();
            
            currentQuizData = result.data;
            renderQuiz(currentQuizData);
            
            // AI 處理完畢，解鎖按鈕
            const quizBtn = document.getElementById('btn-show-quiz');
            quizBtn.disabled = false;
            quizBtn.className = "w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded hover:bg-indigo-700 shadow-lg transition duration-300 transform hover:-translate-y-1";
            quizBtn.textContent = "💡 題目準備就緒！點擊開始測驗";
            
        } catch (e) {
            const quizBtn = document.getElementById('btn-show-quiz');
            quizBtn.textContent = "❌ 出題失敗，請重新整理頁面";
            quizBtn.classList.replace('bg-gray-400', 'bg-red-500');
        }
    }

    // 5. 點擊開始測驗按鈕 (顯示測驗區)
    document.getElementById('btn-show-quiz').addEventListener('click', () => {
        document.getElementById('btn-show-quiz').classList.add('hidden'); // 隱藏按鈕
        document.getElementById('quiz-section').classList.remove('hidden'); // 顯示測驗
        // 自動往下捲動到測驗區
        document.getElementById('quiz-section').scrollIntoView({ behavior: 'smooth' });
    });

    // 6. 渲染題目 HTML
    function renderQuiz(quizArray) {
        const container = document.getElementById('quiz-container');
        container.innerHTML = '';
        
        quizArray.forEach((q, index) => {
            let optionsHtml = '';
            q.options.forEach(opt => {
                optionsHtml += `
                    <label class="block mt-2 p-3 border rounded cursor-pointer hover:bg-indigo-50 transition">
                        <input type="radio" name="q_${index}" value="${opt}" class="mr-2 accent-indigo-600">
                        <span class="text-gray-800">${opt}</span>
                    </label>`;
            });

            container.insertAdjacentHTML('beforeend', `
                <div class="p-5 border rounded shadow-sm quiz-item bg-white" id="quiz-box-${index}">
                    <p class="font-bold text-lg mb-3 text-gray-900">${index + 1}. ${q.question}</p>
                    ${optionsHtml}
                    <div id="explanation-${index}" class="hidden mt-4 p-4 bg-green-50 text-green-900 border-l-4 border-green-500 rounded">
                        <strong>Correct Answer: ${q.correct_answer}</strong><br>
                        <p class="mt-2 text-sm text-gray-700">📝 解析：${q.explanation}</p>
                    </div>
                </div>
            `);
        });
    }

    // 7. 對答案邏輯
    // 確保外面有 async！
    document.getElementById('btn-submit-quiz').addEventListener('click', async () => {
        try {
            const historyRecords = []; 

            if (!currentQuizData || currentQuizData.length === 0) {
                console.error('找不到測驗資料');
                return;
            }

            // 1. 批改答案並收集紀錄
            currentQuizData.forEach((q, index) => {
                const selected = document.querySelector(`input[name="q_${index}"]:checked`);
                const box = document.getElementById(`quiz-box-${index}`);
                const explanation = document.getElementById(`explanation-${index}`);
                
                if (explanation) explanation.classList.remove('hidden');
                
                const userChoice = selected ? selected.value : '未作答';
                const isCorrect = (userChoice === q.correct_answer);

                // 畫面直接顯示答對(綠)或答錯(紅)
                if (isCorrect) {
                    if (box) box.classList.add('border-green-400', 'bg-green-50');
                } else {
                    if (box) box.classList.add('border-red-400', 'bg-red-50');
                }

                historyRecords.push({
                    question: q.question,
                    user_choice: userChoice,
                    correct_answer: q.correct_answer,
                    explanation: q.explanation,
                    is_correct: isCorrect
                });
            });


            document.getElementById('btn-submit-quiz').classList.add('hidden');

            // 2. 將陣列發送給後端 API
            const currentToken = localStorage.getItem('auth_token');
            const apiUrl = 'http://127.0.0.1:8000/api/reading/save-history'; 

            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${currentToken}`,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ records: historyRecords })
            });

            const result = await res.json();
            
            if (res.ok) {
                console.log('✅ 測驗細節成功存入資料庫');
            } else {
                throw new Error(`伺服器錯誤: ${JSON.stringify(result)}`);
            }

        } catch (e) {
            console.error('❌ 紀錄儲存失敗：', e);
        }
    });
});