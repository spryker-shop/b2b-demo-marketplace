// GuiAssistant JS
require('../sass/gui-assistant.scss')
require('../img/gui-assistant-submit.svg')
require('../img/gui-assistant-icon.svg')

const icon = document.getElementById('gui-assistant-icon');
const chat = document.getElementById('gui-assistant-chat');
const minimize = document.getElementById('gui-assistant-minimize');
const input = document.getElementById('gui-assistant-input');
const submit = document.getElementById('gui-assistant-submit');
const history = document.getElementById('gui-assistant-history');
const status = document.getElementById('gui-assistant-status');
const countdown = document.getElementById('gui-assistant-countdown');
const toolInfoToggle = document.getElementById('gui-assistant-toolinfo-toggle');
const dragHandle = document.getElementById('gui-assistant-drag');
const chatBox = document.getElementById('gui-assistant-chat');

// File upload elements
const fileInput = document.getElementById('gui-assistant-file-input');
const fileButton = document.getElementById('gui-assistant-file-button');
const filePreview = document.getElementById('gui-assistant-file-preview');
const fileName = filePreview.querySelector('.gui-assistant-file-name');
const fileRemove = filePreview.querySelector('.gui-assistant-file-remove');
const dropOverlay = document.getElementById('gui-assistant-drop-overlay');

let enabled = true;
let username = icon.getAttribute('data-username');
let assistantName = icon.getAttribute('data-assistantname');
let conversation = [];
let countdownInterval = null;
let countdownSeconds = 0;
let timeout = 60; // seconds
let responseTimeoutId = null;
let showToolInfo = false;
let isDragging = false;
let dragStartX = 0;
let dragStartY = 0;
let startWidth = 0;
let startHeight = 0;

// File upload variables
let attachedFile = null;

function renderHistory() {
    history.innerHTML = '';
    conversation.forEach(msg => {
        const isToolInfo = msg.meta === 'tool-info';
        if (isToolInfo && !showToolInfo) return;

        const div = document.createElement('div');
        let label;
        let msgText = msg.text;
        div.type = msg.meta !== 'default' ? msg.meta : msg.type;
        div.className = 'gui-assistant-message';
        switch (div.type) {
            case 'image':
            case 'pdf':
            case 'txt':
                div.className += ' gui-assistant-message-user';
                label = username;
                msgText = 'File Upload (' + div.type + ')';

                break;
            case 'tool-info':
                div.className += ' gui-assistant-message-tool-info';
                label = 'Tool Info';
                break;
            case 'user':
                div.className += ' gui-assistant-message-user';
                label = username;
                break;
            case 'error':
                div.className += ' gui-assistant-message-assistant';
                label = 'ERROR';
                break;
            default:
            case 'assistant':
                div.className += ' gui-assistant-message-assistant';
                label = assistantName;
                break;
        }
        let responseTimeHtml = '';
        if (showToolInfo && msg.responseTime !== undefined) {
            responseTimeHtml = `<span style="color:gray;font-size:10px;float:right;">${msg.responseTime}s</span>`;
        }
        div.innerHTML = `<div class=\"gui-assistant-message-content\"><strong>${label}:</strong><pre>${msgText}</pre>${responseTimeHtml}</div>`;
        history.appendChild(div);
    });
    history.scrollTop = history.scrollHeight;
}

function startCountdown(seconds) {
    countdownSeconds = seconds;
    countdown.textContent = `Time left: ${countdownSeconds}s`;
    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(() => {
        countdownSeconds--;
        if (countdownSeconds <= 0) {
            clearInterval(countdownInterval);
            countdown.textContent = '';
        } else {
            countdown.textContent = `Time left: ${countdownSeconds}s`;
        }
    }, 1000);
}

function stopCountdown() {
    if (countdownInterval) clearInterval(countdownInterval);
    countdown.textContent = '';
}

function setStatus(text, isEnabled) {
    status.textContent = text;
    enabled = isEnabled;
    input.disabled = !enabled;
    submit.disabled = !enabled;
    if (!isEnabled) {
        startCountdown(timeout);
    } else {
        stopCountdown();
        if (responseTimeoutId) {
            clearTimeout(responseTimeoutId);
            responseTimeoutId = null;
        }
    }
}

icon.addEventListener('click', () => {
    chat.style.display = 'flex';
    icon.style.display = 'none';
});
minimize.addEventListener('click', () => {
    chat.style.display = 'none';
    icon.style.display = 'flex';
});

input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (enabled) submitMessage();
    }
});
submit.addEventListener('click', function() {
    if (enabled) submitMessage();
});

toolInfoToggle.addEventListener('change', function() {
    showToolInfo = this.checked;
    renderHistory();
});

dragHandle.addEventListener('mousedown', function(e) {
    isDragging = true;
    dragStartX = e.clientX;
    dragStartY = e.clientY;
    startWidth = chatBox.offsetWidth;
    startHeight = chatBox.offsetHeight;
    document.body.style.userSelect = 'none';
});
document.addEventListener('mousemove', function(e) {
    if (!isDragging) return;
    let newWidth = Math.min(Math.max(startWidth + (dragStartX - e.clientX), 500), 1200);
    let newHeight = Math.min(Math.max(startHeight + (dragStartY - e.clientY), 250), 1000);
    chatBox.style.width = newWidth + 'px';
    chatBox.style.height = newHeight + 'px';
});
document.addEventListener('mouseup', function() {
    if (isDragging) {
        isDragging = false;
        document.body.style.userSelect = '';
    }
});

// File upload event handlers
fileButton.addEventListener('click', () => {
    fileInput.click();
});

fileInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        attachFile(file);
    }
});

fileRemove.addEventListener('click', () => {
    detachFile();
});

// Drag and drop handlers
chat.addEventListener('dragover', (e) => {
    e.preventDefault();
    chat.classList.add('drag-active');
    dropOverlay.style.display = 'flex';
});

chat.addEventListener('dragleave', (e) => {
    if (!chat.contains(e.relatedTarget)) {
        chat.classList.remove('drag-active');
        dropOverlay.style.display = 'none';
    }
});

chat.addEventListener('drop', (e) => {
    e.preventDefault();
    chat.classList.remove('drag-active');
    dropOverlay.style.display = 'none';

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        attachFile(files[0]);
    }
});

function attachFile(file) {
    // Validate file type
    const allowedTypes = ['.txt'];
    const fileExt = '.' + file.name.split('.').pop().toLowerCase();

    if (!allowedTypes.includes(fileExt)) {
        alert('File type not supported. Allowed types: ' + allowedTypes.join(', '));
        return;
    }

    // Validate file size (0.5MB limit ~ 100k tokens)
    if (file.size > 0.5 * 1024 * 1024) {
        alert('File too large. Maximum size is 0.5MB .');
        return;
    }

    attachedFile = file;
    fileName.textContent = file.name;
    filePreview.style.display = 'block';
    fileInput.value = ''; // Clear input
}

function detachFile() {
    attachedFile = null;
    filePreview.style.display = 'none';
    fileInput.value = ''; // Clear input
}

function submitMessage() {
    const text = input.value.trim();
    if (!text) return;

    if (attachedFile) {
        const fileExt = '.' + attachedFile.name.split('.').pop().toLowerCase();
        const fileType = ['.jpg', '.jpeg', '.png', '.gif'].includes(fileExt) ? 'image' : (fileExt === '.pdf' ? 'pdf' : 'txt');
        // Read file as base64 for images
        const reader = new FileReader();
        reader.onload = function(e) {
            // base64encoded already (includes "data:image/jpeg;base64,") or simple text
            const fileContent = e.target.result;

            sendMessageWithContent(text, fileType, fileContent);
            detachFile();
        };

        if (fileType === 'txt') {
            reader.readAsText(attachedFile, 'utf-8');
        } else {
            reader.readAsDataURL(attachedFile);
        }
    } else {
        sendMessageWithContent(text);
    }

}

function sendMessageWithContent(messageText, fileType = null, fileContent = null) {
    conversation.push({type: 'user', text: messageText, 'meta': 'default'});

    if (fileContent) {
        conversation.push({type: fileType, content: fileContent, meta: fileType});
    }

    const messages = conversation
        .filter(item => item.type !== 'error')
        .map(msg => ({
            role: msg.type,
            content: msg.text || msg.content
        }));

    renderHistory();
    input.value = '';
    setStatus('In progress...', false);

    const fetchStart = Date.now(); // Start timing
    fetch('/gui-assistant/chat/send', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({messages, username})
    })
        .then(resp => resp.json())
        .then(data => {
            if (data.error) {
                conversation.push({type: 'error', text: data.error, 'meta': 'default'});
                renderHistory();
                setStatus('Enabled', true);

                return;
            }

            const answer = data.answer || '[No answer]';
            const answers = Array.isArray(answer) ? answer : [answer];

            const responseTime = Math.round((Date.now() - fetchStart) / 1000);
            answers.forEach(item => {
                let meta = (typeof item === 'string' && (item.startsWith('Calling Endpoint:') || item.startsWith('Endpoint answered:'))) ? 'tool-info' : 'default';
                conversation.push({type: 'assistant', text: item, meta: meta, responseTime: responseTime});
            });

            renderHistory();
            setStatus('Enabled', true);
        })
        .catch(() => {
            conversation.push({type: 'error', text: 'Unexpected error', 'meta': 'default'});
            renderHistory();
            setStatus('Enabled', true);
        });
    if (responseTimeoutId) {
        clearTimeout(responseTimeoutId);
    }
    responseTimeoutId = setTimeout(() => {
        if (!enabled) {
            conversation.push({type: 'assistant', text: '[Timeout: No response from ' + assistantName + ']'});
            renderHistory();
            setStatus('Enabled', true);
        }
    }, (timeout + 5) * 1000);
}

window.guiAssistantSetUsername = function(name) { username = name; };
window.guiAssistantSetAssistantName = function(name) { assistantName = name; };
