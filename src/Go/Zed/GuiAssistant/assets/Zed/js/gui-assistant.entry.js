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

let enabled = true;
let username = icon.getAttribute('data-username');
let assistantName = icon.getAttribute('data-assistantname');
let conversation = [];
let countdownInterval = null;
let countdownSeconds = 0;
let timeout = 60; // seconds
let responseTimeoutId = null;

function renderHistory() {
    history.innerHTML = '';
    conversation.forEach(msg => {
        const div = document.createElement('div');
        let label;
        div.type = msg.meta !== 'default' ? msg.meta : msg.type;
        div.className = 'gui-assistant-message';
        switch (div.type) {
            case 'tool-info':
                div.className += ' gui-assistant-message-tool-info';
                label = 'Tool Info';
                break;
            case 'user':
                div.className += ' gui-assistant-message-user';
                label = username;
                break;
            default:
            case 'assistant':
                div.className += ' gui-assistant-message-assistant';
                label = assistantName;
                break;
        }
        div.innerHTML = `<div class=\"gui-assistant-message-content\"><strong>${label}:</strong><pre>${msg.text}</pre></div>`;
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

function submitMessage() {
    const text = input.value.trim();
    if (!text) return;

    conversation.push({type: 'user', text, 'meta': 'default'});

    const messages = conversation
        .map(msg => ({
            role: msg.type,
            content: msg.text
        }));

    renderHistory();
    input.value = '';
    setStatus('In progress...', false);
    fetch('/gui-assistant/chat/send', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({messages, username})
    })
        .then(resp => resp.json())
        .then(data => {
            const answer = data.answer || '[No answer]';
            const answers = Array.isArray(answer) ? answer : [answer];
            answers.forEach(item => {
                let meta = (typeof item === 'string' && (item.startsWith('Calling Endpoint:') || item.startsWith('Endpoint answered:'))) ? 'tool-info' : 'default'
                conversation.push({type: 'assistant', text: item, meta: meta});
            });
            renderHistory();
            setStatus('Enabled', true);
        })
        .catch(() => {
            conversation.push({type: 'assistant', text: '[Error: No response from ' + assistantName + ']'});
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
