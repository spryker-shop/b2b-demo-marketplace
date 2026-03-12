'use strict';

function initBackofficeAssistant() {
    const toggle = document.querySelector('.js-backoffice-assistant__toggle');
    const panel = document.querySelector('.js-backoffice-assistant__panel');

    if (!toggle || !panel) {
        return;
    }

    const closeBtn = panel.querySelector('.js-backoffice-assistant__close');
    const historyBtn = panel.querySelector('.js-backoffice-assistant__history-btn');
    const newChatBtn = panel.querySelector('.js-backoffice-assistant__new-chat');
    const messagesEl = panel.querySelector('.js-backoffice-assistant__messages');
    const historiesEl = panel.querySelector('.js-backoffice-assistant__histories');
    const historiesList = panel.querySelector('.js-backoffice-assistant__histories-list');
    const historiesEmpty = panel.querySelector('.js-backoffice-assistant__histories-empty');
    const inputEl = panel.querySelector('.js-backoffice-assistant__input');
    const sendBtn = panel.querySelector('.js-backoffice-assistant__send');

    const STORAGE_KEY = 'backoffice_assistant_state';

    let currentConversationReference = null;
    let isWaiting = false;
    let greetingShown = false;

    function saveState() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                isOpen: panel.classList.contains('backoffice-assistant__panel--open'),
                conversationReference: currentConversationReference,
            }));
        } catch (e) {
            // localStorage may be unavailable in some environments
        }
    }

    function loadState() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);

            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function getUserName() {
        return (window.BackofficeAssistantConfig && window.BackofficeAssistantConfig.userName) || 'there';
    }

    function openPanel() {
        panel.classList.add('backoffice-assistant__panel--open');
        toggle.hidden = true;
        saveState();

        if (!greetingShown && !currentConversationReference) {
            showGreeting();
        }
    }

    function closePanel() {
        panel.classList.remove('backoffice-assistant__panel--open');
        toggle.hidden = false;
        saveState();
    }

    function showGreeting() {
        greetingShown = true;
        addMessage('ai', 'Hello, ' + getUserName() + '! How can I help you today?');
    }

    function showHistoriesView() {
        historiesEl.hidden = false;
        messagesEl.classList.add('backoffice-assistant__messages--hidden');
        loadHistories();
    }

    function hideHistoriesView() {
        historiesEl.hidden = true;
        messagesEl.classList.remove('backoffice-assistant__messages--hidden');
    }

    function startNewConversation() {
        currentConversationReference = null;
        greetingShown = false;
        messagesEl.innerHTML = '';
        hideHistoriesView();
        inputEl.value = '';
        inputEl.focus();
        showGreeting();
        saveState();
    }

    function addMessage(role, text) {
        const bubble = document.createElement('div');
        bubble.classList.add('backoffice-assistant__message', 'backoffice-assistant__message--' + role);
        bubble.textContent = text;
        messagesEl.appendChild(bubble);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        return bubble;
    }

    function addLoadingIndicator() {
        const el = document.createElement('div');
        el.classList.add('backoffice-assistant__message', 'backoffice-assistant__message--ai', 'backoffice-assistant__message--loading');
        el.innerHTML = '<span class="backoffice-assistant__typing-dot"></span>' +
            '<span class="backoffice-assistant__typing-dot"></span>' +
            '<span class="backoffice-assistant__typing-dot"></span>';
        messagesEl.appendChild(el);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        return el;
    }

    function addRetryButton(prompt) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.classList.add('backoffice-assistant__retry-btn');
        btn.textContent = 'Retry';
        btn.addEventListener('click', function () {
            btn.remove();
            doSendMessage(prompt);
        });
        messagesEl.appendChild(btn);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function getBreadcrumb() {
        const breadcrumbEl = document.querySelector('.breadcrumb');

        if (breadcrumbEl) {
            return breadcrumbEl.textContent.trim().replace(/\s+/g, ' ');
        }

        return window.location.pathname;
    }

    function parseSseData(text) {
        const lines = text.split('\n');

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];

            if (line.startsWith('data: ')) {
                return JSON.parse(line.slice(6));
            }
        }

        throw new Error('No SSE data line found in response');
    }

    function setWaiting(waiting) {
        isWaiting = waiting;
        sendBtn.disabled = waiting;
        inputEl.disabled = waiting;
    }

    function doSendMessage(prompt) {
        addMessage('user', prompt);
        setWaiting(true);

        const loadingEl = addLoadingIndicator();
        const body = { prompt: prompt, context: getBreadcrumb() };

        if (currentConversationReference) {
            body.conversation_reference = currentConversationReference;
        }

        fetch('/backoffice-assistant/prompt/index', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        })
            .then(function (response) {
                return response.text();
            })
            .then(function (text) {
                loadingEl.remove();

                const data = parseSseData(text);

                if (data.error) {
                    addMessage('ai', 'Error: ' + data.error);
                    addRetryButton(prompt);
                } else {
                    currentConversationReference = data.conversation_reference;
                    saveState();
                    addMessage('ai', data.ai_response || '');
                }
            })
            .catch(function () {
                loadingEl.remove();
                addMessage('ai', 'Connection error. Please try again.');
                addRetryButton(prompt);
            })
            .finally(function () {
                setWaiting(false);
            });
    }

    function sendMessage() {
        const prompt = inputEl.value.trim();

        if (!prompt || isWaiting) {
            return;
        }

        inputEl.value = '';
        resizeInput();
        doSendMessage(prompt);
    }

    function loadHistories() {
        fetch('/backoffice-assistant/conversation-histories/index', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (histories) {
                historiesList.innerHTML = '';

                if (!histories || histories.length === 0) {
                    historiesEmpty.hidden = false;

                    return;
                }

                historiesEmpty.hidden = true;
                histories.forEach(function (entry) {
                    const li = document.createElement('li');
                    li.classList.add('backoffice-assistant__histories-item');
                    li.textContent = entry.name || entry.conversation_reference;
                    li.addEventListener('click', function () {
                        loadConversationDetail(entry.conversation_reference);
                    });
                    historiesList.appendChild(li);
                });
            })
            .catch(function () {
                historiesEmpty.hidden = false;
                historiesEmpty.textContent = 'Failed to load conversations.';
            });
    }

    function loadConversationDetail(ref) {
        currentConversationReference = ref;
        saveState();
        hideHistoriesView();
        messagesEl.innerHTML = '';

        fetch('/backoffice-assistant/conversation-histories/detail?conversationReference=' + encodeURIComponent(ref), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                const messages = Array.isArray(data.messages) ? data.messages : [];
                messages.forEach(function (msg) {
                    const role = msg.type === 'user' ? 'user' : 'ai';
                    addMessage(role, msg.content || '');
                });
                inputEl.focus();
            })
            .catch(function () {
                addMessage('ai', 'Failed to load conversation history.');
            });
    }

    function resizeInput() {
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 120) + 'px';
    }

    // Restore state from previous page
    (function restorePersistedState() {
        const state = loadState();

        if (!state) {
            return;
        }

        if (state.conversationReference) {
            currentConversationReference = state.conversationReference;
        }

        if (state.isOpen) {
            panel.classList.add('backoffice-assistant__panel--open');
            toggle.hidden = true;
            greetingShown = true;

            if (currentConversationReference) {
                loadConversationDetail(currentConversationReference);
            } else {
                showGreeting();
            }
        }
    }());

    // Event bindings
    toggle.addEventListener('click', function () {
        if (panel.classList.contains('backoffice-assistant__panel--open')) {
            closePanel();
        } else {
            openPanel();
        }
    });

    closeBtn.addEventListener('click', closePanel);
    historyBtn.addEventListener('click', showHistoriesView);
    newChatBtn.addEventListener('click', startNewConversation);
    sendBtn.addEventListener('click', sendMessage);

    inputEl.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });

    inputEl.addEventListener('input', resizeInput);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && panel.classList.contains('backoffice-assistant__panel--open')) {
            closePanel();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBackofficeAssistant);
} else {
    initBackofficeAssistant();
}
