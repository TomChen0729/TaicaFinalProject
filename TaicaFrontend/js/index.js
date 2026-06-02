// ==========================================
// 1. 驗證與全域參數初始化
// ==========================================
const token = localStorage.getItem('auth_token');
// 在 index.js 全域宣告一個阻擋鎖
let isSending = false;
let isChallengePassed = false; // 🔹 核心新增：關卡是否已通關的終極狀態鎖
// 🔹 修正全域守衛：只有在進入需要「強制登入」的頁面時才進行攔截，允許首頁訪客模式正常運作
const loginRequiredPages = ['chat.html', 'test.html'];
const isPageRequiredLogin = loginRequiredPages.some(page => window.location.pathname.includes(page));

if (!token && isPageRequiredLogin) {
    alert('偵測到未登入行為，請先登入會員以開始練習！');
    window.location.href = 'auth/login.html'; 
}

// 解析網址情境參數
const urlParams = new URLSearchParams(window.location.search);
const currentScenario = urlParams.get('scenario') || 'fast_food';

// DOM 元素宣告
const recordButton = document.getElementById('recordButton');
const statusText = document.getElementById('statusText');
const chatMessages = document.getElementById('chatMessages');

let mediaRecorder;
let audioChunks = [];
let isRecording = false;

// ==========================================
// 2. 動態從後端資料庫獲取情境配置
// ==========================================
async function initScenarioConfig() {
    try {
        if (statusText) statusText.textContent = '正在載入關卡設定...';

        const response = await fetch(`http://127.0.0.1:8000/api/scenarios/${currentScenario}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) throw new Error('無法取得該關卡的資料庫設定');

        const config = await response.json();
        setupUI(config);

    } catch (error) {
        console.error('初始化失敗:', error);
        alert('關卡資料載入失敗，將自動套用備用速食店設定。');
        setupUI({
            title: '🍔 Fast Food Drive-Thru',
            task: '任務：用英文點一份辣味麥脆雞套餐',
            greeting: 'Hi, welcome! What can I get for you today?',
            color: '#ef4444'
        });
    }
}

// 渲染 UI 的獨立邏輯
function setupUI(config) {
    const taskHintElement = document.getElementById('ui-task');
    const greetingElement = document.getElementById('ui-greeting');

    if (taskHintElement) taskHintElement.textContent = config.task;
    if (greetingElement) greetingElement.textContent = config.greeting;

    if (!document.getElementById('uploadTestBtn')) {
        const header = document.getElementById('ui-header');
        if (header) {
            header.textContent = config.title;
            header.style.backgroundColor = config.color;
        }
        
        if (recordButton) {
            recordButton.style.backgroundColor = config.color;
        }
        
        const style = document.createElement('style');
        style.innerHTML = `.message.user { background-color: ${config.color} !important; }`;
        document.head.appendChild(style);
    }

    if (statusText) statusText.textContent = '等待錄音...';
}

initScenarioConfig();


// ==========================================
// 3. 輔助功能 (UI 更新與 TTS 播放)
// ==========================================

// 🔹 升級：支援音訊播放器嵌入的訊息泡泡渲染 (達成解耦不吃行內樣式)
function addMessage(text, sender, audioUrl = null) {
    if (!chatMessages) return;
    const msgDiv = document.createElement('div');
    msgDiv.classList.add('message', sender);
    
    // 🔹 解決文字覆蓋問題：用 span 包裹文字內容，之後動態更新只更新這個 span
    const textSpan = document.createElement('span');
    textSpan.className = 'msg-text';
    textSpan.textContent = text;
    msgDiv.appendChild(textSpan);

    // 🔹 核心新增：如果有點擊錄音產生的音訊暫時網址，就掛載一個原生精簡播放器
    if (audioUrl) {
        const audioEl = document.createElement('audio');
        audioEl.controls = true;
        audioEl.src = audioUrl;
        audioEl.className = 'user-audio-playback'; // 樣式交由 index.css 控制
        audioEl.style.display = 'block';
        audioEl.style.marginTop = '8px';
        audioEl.style.maxWidth = '100%';
        msgDiv.appendChild(audioEl);
    }

    chatMessages.appendChild(msgDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function playTTS(text) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel(); 
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        utterance.rate = 0.9;
        window.speechSynthesis.speak(utterance);
    } else {
        console.warn("此瀏覽器不支援 Web Speech API");
    }
}

// ==========================================
// 4. 麥克風錄音邏輯
// ==========================================
async function startRecording() {
    // 🔹 核心防禦：如果此關卡已經通關成功，直接阻斷錄音功能，不允許往下執行
    if (isChallengePassed) {
        if (statusText) statusText.textContent = '🏆 關卡已成功通關，錄音功能已停用。';
        return;
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];

        mediaRecorder.ondataavailable = event => {
            if (event.data.size > 0) audioChunks.push(event.data);
        };

        mediaRecorder.onstop = async () => {
            if (statusText) statusText.textContent = '處理中，請稍候...';
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            stream.getTracks().forEach(track => track.stop());
            await sendToLaravel(audioBlob);
        };

        mediaRecorder.start();
        isRecording = true;
        
        if (recordButton) {
            recordButton.textContent = '鬆開停止 (Release to Stop)';
            recordButton.classList.add('recording');
        }
        if (statusText) statusText.textContent = '正在聆聽您的語音...';

    } catch (error) {
        console.error('麥克風權限獲取失敗:', error);
        if (statusText) statusText.textContent = '無法錄音，請確認設備或使用上傳功能';
    }
}

function stopRecording() {
    if (mediaRecorder && isRecording) {
        mediaRecorder.stop();
        isRecording = false;
        if (recordButton) {
            recordButton.textContent = '按住說話 (Hold to Speak)';
            recordButton.classList.remove('recording');
        }
    }
}

if (recordButton) {
    recordButton.addEventListener('mousedown', startRecording);
    recordButton.addEventListener('mouseup', stopRecording);
    recordButton.addEventListener('mouseleave', stopRecording);
    recordButton.addEventListener('touchstart', (e) => { e.preventDefault(); startRecording(); });
    recordButton.addEventListener('touchend', (e) => { e.preventDefault(); stopRecording(); });
}

// ==========================================
// 5. 與 Laravel API 溝通的核心邏輯
// ==========================================
/**
 * 與 Laravel API 溝通的核心非同步邏輯（全功能整合版）
 */
async function sendToLaravel(audioData) {
    // 🔹 1. 頂層雙重防禦機制：如果已經通關或有請求正在發送中，直接攔截阻斷，拒絕任何 API 呼叫
    if (isChallengePassed || isSending) return;
    isSending = true;

    // 🔹 2. 利用 Blob 生成瀏覽器快取記憶體內的暫時音訊網址（免去後端傳輸等待）
    const localAudioUrl = URL.createObjectURL(audioData);

    // 🔹 3. 在非同步請求發出前，立刻渲染使用者的對話泡泡，並直接嵌入剛剛的錄音播放器
    addMessage('...', 'user', localAudioUrl);
    
    // 封裝二進位多媒體表單資料
    const formData = new FormData();
    formData.append('audio', audioData, 'voice.webm');
    formData.append('scenario', currentScenario);

    try {
        const response = await fetch('http://127.0.0.1:8000/api/chat', {
            method: 'POST',
            body: formData,
            headers: {
                'Authorization': `Bearer ${token}`, // 帶上 JWT 憑證的安全標頭
                'Accept': 'application/json'
            }
        });

        // 處理 Token 憑證過期狀況
        if (response.status === 401) {
            alert('登入憑證已過期，請重新登入。');
            localStorage.clear();
            window.location.href = 'auth/login.html';
            return;
        }

        if (!response.ok) {
            throw new Error(`HTTP 錯誤！狀態碼: ${response.status}`);
        }

        const data = await response.json();
        
        // 🔹 4. 安全文字替換：精準找到點對點的 .msg-text 容器抽換文字，「完美保留」剛產生的錄音播放器
        if (chatMessages && chatMessages.lastChild) {
            const textSpan = chatMessages.lastChild.querySelector('.msg-text');
            if (textSpan) {
                textSpan.textContent = data.user_text || '(無法辨識語音)';
            } else {
                chatMessages.lastChild.textContent = data.user_text || '(無法辨識語音)';
            }

            console.log('使用者語音已成功發送並更新文字內容', data.pronunciation_fix);
            // 🔹 5. 發音與表達糾正動態嵌入：若後端有回傳修正提示，就地掛載至該對話泡泡底部
            if (data.pronunciation_fix) {
                // 防呆機制：確保同一個泡泡內不會重複附加提示
                const existingTip = chatMessages.lastChild.querySelector('.pronunciation-tip');
                if (!existingTip) {
                    const tipDiv = document.createElement('div');
                    tipDiv.className = 'pronunciation-tip'; // 樣式完全解耦，交由 index.css 管理
                    tipDiv.textContent = `📢 發音/表達調整：${data.pronunciation_fix}`;
                    chatMessages.lastChild.appendChild(tipDiv);
                }
            }
        }
        
        // 渲染 AI 角色的情境回應並播放語音 TTS
        if (data.ai_reply) {
            addMessage(data.ai_reply, 'ai');
            playTTS(data.ai_reply);
        }

        // ==========================================
        // 6. 狀態與通關邏輯雙向判定 (成功 vs 繼續挑戰)
        // ==========================================
        if (data.is_success) {
            // 🟢 Case 1：完全達成情境任務目標
            isChallengePassed = true; // 開啟通關鎖，從此封鎖麥克風錄音

            if (statusText) statusText.textContent = '🏆 關卡已成功通關！錄音功能已關閉。';
            
            // 前端視覺去活化控制：讓按鈕完全無法被點擊、視覺變灰
            if (recordButton) {
                recordButton.textContent = '🎉 任務已成功完成 (Task Completed)';
                recordButton.style.pointerEvents = 'none'; // 滑鼠點擊完全穿透
                recordButton.style.opacity = '0.5';         // 灰色去活化外觀
                recordButton.style.cursor = 'not-allowed';
            }
            
            // 延時 1 秒跳出祝賀彈窗（不自動進行頁面導向，留在原地）
            setTimeout(() => {
                alert('🎉 恭喜通關！成功達成該情境的生存任務！');
            }, 1000);
            
        } else {
            // 🔴 Case 2：尚未完全達成目標（對話繼續進行，處理原本遺漏的狀態）
            if (statusText) {
                statusText.textContent = '🎤 尚未完全達成目標，請聽取 AI 回應並繼續對話...';
            }
            
            // 延時防呆：3 秒後若使用者沒有正在開啟下一次錄音，且提示還在，就悄悄恢復等待字樣
            setTimeout(() => {
                if (statusText && statusText.textContent.includes('繼續對話') && !isRecording && !isChallengePassed) {
                    statusText.textContent = '等待錄音...';
                }
            }, 3000);
        }

    } catch (error) {
        console.error('API 錯誤:', error);
        if (statusText) statusText.textContent = '連線失敗，請確認 Laravel 伺服器已啟動';
        
        // 發送失敗時的文字提示防禦
        if (chatMessages && chatMessages.lastChild) {
            const textSpan = chatMessages.lastChild.querySelector('.msg-text');
            if (textSpan) textSpan.textContent = '[語音發送失敗]';
        }
    } finally {
        // 🔹 7. 關鍵解鎖：不論執行成功或拋出 catch 錯誤，最後一定要釋放發送鎖，否則下一次錄音會被永久卡死
        isSending = false;
    }
}

// ==========================================
// 6. 開發測試專區邏輯 (僅在 test.html 會生效)
// ==========================================
const uploadTestBtn = document.getElementById('uploadTestBtn');
const testAudioInput = document.getElementById('testAudio');

if (uploadTestBtn && testAudioInput) {
    uploadTestBtn.addEventListener('click', async () => {
        if (testAudioInput.files.length === 0) {
            alert('請先選擇一個音檔！');
            return;
        }
        
        const file = testAudioInput.files[0];
        if (statusText) statusText.textContent = '測試音檔發送中...';
        
        await sendToLaravel(file);
    });
}

// ==========================================
// 7. 大首頁互動與權限分流邏輯 (解耦版)
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    const apiUrl = 'http://127.0.0.1:8000/api';
    const currentToken = localStorage.getItem('auth_token');
    const userName = localStorage.getItem('user_name');
    
    const navRightActions = document.getElementById('navRightActions');
    const authCheckCards = document.querySelectorAll('.auth-check-card');

    if (navRightActions) {
        if (currentToken && userName) {
            navRightActions.innerHTML = `
                <span style="color: #cbd5e1;">歡迎回來，${userName} 同學 👋</span>
                <span style="color: #475569;">|</span>
                <a href="dashboard.html" class="nav-link">進入我的後台 📊</a>
                <button id="logoutBtn" class="btn-logout">安全登出</button>
            `;

            document.getElementById('logoutBtn').addEventListener('click', async () => {
                try {
                    await fetch(`${apiUrl}/logout`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${currentToken}`,
                            'Accept': 'application/json'
                        }
                    });
                } catch (e) {
                    console.error('後端 Token 銷毀失敗，直接清除本機快取');
                } finally {
                    localStorage.clear();
                    alert('已安全登出！');
                    window.location.reload();
                }
            });

        } else {
            navRightActions.innerHTML = `
                <span style="color: #94a3b8; font-size: 0.85rem;">🔒 訪客模式</span>
                <a href="Auth/login.html" class="btn-login">會員登入 / 註冊</a>
            `;

            authCheckCards.forEach(card => {
                card.addEventListener('click', (e) => {
                    e.preventDefault();
                    alert('請先登入會員以解鎖訓練模組！');
                    window.location.href = 'Auth/login.html';
                });
                
                const btn = card.querySelector('.btn-enter');
                if (btn) {
                    btn.textContent = '請先登入';
                    btn.classList.add('btn-guest'); 
                }
            });
        }
    }
});