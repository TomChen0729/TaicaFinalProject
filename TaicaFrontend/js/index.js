// ==========================================
// 1. 驗證與全域參數初始化
// ==========================================
const token = localStorage.getItem('auth_token');

if (!token) {
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
// 2. 動態從後端資料庫獲取情境配置 (核心改動)
// ==========================================
async function initScenarioConfig() {
    try {
        if (statusText) statusText.textContent = '正在載入關卡設定...';

        // 呼叫我們剛建好的 Laravel 路由
        const response = await fetch(`http://127.0.0.1:82/api/scenarios/${currentScenario}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) throw new Error('無法取得該關卡的資料庫設定');

        const config = await response.json();
        
        // 動態渲染 UI
        setupUI(config);

    } catch (error) {
        console.error('初始化失敗:', error);
        alert('關卡資料載入失敗，將自動套用備用速食店設定。');
        // 備用防崩潰機制
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

    // 若非測試頁面，動態上色
    if (!document.getElementById('uploadTestBtn')) {
        const header = document.getElementById('ui-header');
        if (header) {
            header.textContent = config.title;
            header.style.backgroundColor = config.color;
        }
        
        if (recordButton) {
            recordButton.style.backgroundColor = config.color;
        }
        
        // 動態注入 CSS 改變對話泡泡顏色
        const style = document.createElement('style');
        style.innerHTML = `.message.user { background-color: ${config.color} !important; }`;
        document.head.appendChild(style);
    }

    if (statusText) statusText.textContent = '等待錄音...';
}

// 執行頁面初始化
initScenarioConfig();


// ==========================================
// 3. 輔助功能 (UI 更新與 TTS 播放)
// ==========================================
function addMessage(text, sender) {
    if (!chatMessages) return;
    const msgDiv = document.createElement('div');
    msgDiv.classList.add('message', sender);
    msgDiv.textContent = text;
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
async function sendToLaravel(audioData) {
    addMessage('...', 'user');
    
    const formData = new FormData();
    formData.append('audio', audioData, 'voice.webm');
    formData.append('scenario', currentScenario);

    try {
        const response = await fetch('http://127.0.0.1:82/api/chat', {
            method: 'POST',
            body: formData,
            headers: {
                'Authorization': `Bearer ${token}`, // 不要忘記第二階段加上來的安全憑證
                'Accept': 'application/json'
            }
        });

        if (response.status === 401) {
            alert('登入憑證已過期，請重新登入。');
            localStorage.clear();
            window.location.href = 'auth/login.html';
            return;
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (chatMessages && chatMessages.lastChild) {
            chatMessages.lastChild.textContent = data.user_text || '(無法辨識語音)';
        }
        
        if (data.ai_reply) {
            addMessage(data.ai_reply, 'ai');
            playTTS(data.ai_reply);
        }

        // 觸發第二階段的通關特效判斷
        if (data.is_success) {
            setTimeout(() => {
                alert('🎉 恭喜通關！成功達成該情境的生存任務！');
                if (confirm('是否前往學習儀表板查看您的最新數據統計？')) {
                    window.location.href = 'dashboard.html';
                }
            }, 1000);
        }

        if (statusText) statusText.textContent = '等待錄音...';

    } catch (error) {
        console.error('API 錯誤:', error);
        if (statusText) statusText.textContent = '連線失敗，請確認 Laravel 伺服器已啟動';
        if (chatMessages && chatMessages.lastChild) {
            chatMessages.lastChild.textContent = '[語音發送失敗]';
        }
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