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

let enabled = true;
let username = icon.getAttribute('data-username');
let assistantName = icon.getAttribute('data-assistantname');
let conversation = [];

function renderHistory() {
    history.innerHTML = '';
    conversation.forEach(msg => {
        const div = document.createElement('div');
        div.className = 'gui-assistant-message ' + (msg.type === 'user' ? 'gui-assistant-message-user' : 'gui-assistant-message-assistant');
        div.innerHTML = `<div class=\"gui-assistant-message-content\">${msg.type === 'user' ? `<strong>${username}:</strong> ` : `<strong>${assistantName}:</strong> `}<pre>${msg.text}</pre></div>`;
        history.appendChild(div);
    });
    history.scrollTop = history.scrollHeight;
}

function setStatus(text, isEnabled) {
    status.textContent = text;
    enabled = isEnabled;
    input.disabled = !enabled;
    submit.disabled = !enabled;
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

    conversation.push({type: 'user', text});

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
                conversation.push({type: 'assistant', text: item});
            });
            renderHistory();
            setStatus('Enabled', true);
        })
        .catch(() => {
            conversation.push({type: 'assistant', text: '[Error: No response from ' + assistantName + ']'});
            renderHistory();
            setStatus('Enabled', true);
        });
    setTimeout(() => {
        if (!enabled) {
            conversation.push({type: 'assistant', text: '[Timeout: No response from ' + assistantName + ']'});
            renderHistory();
            setStatus('Enabled', true);
        }
    }, 125000);
}

window.guiAssistantSetUsername = function(name) { username = name; };
window.guiAssistantSetAssistantName = function(name) { assistantName = name; };
