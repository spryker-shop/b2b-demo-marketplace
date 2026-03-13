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

    const agentBadgeEl = panel.querySelector('.js-backoffice-assistant__agent-badge');
    const attachBtn = panel.querySelector('.js-backoffice-assistant__attach');
    const fileInput = panel.querySelector('.js-backoffice-assistant__file-input');
    const attachmentsPreview = panel.querySelector('.js-backoffice-assistant__attachments-preview');

    const STORAGE_KEY = 'backoffice_assistant_state';
    const MAX_FILE_SIZE = 5 * 1024 * 1024;
    const ALLOWED_TYPES = [
        'image/png', 'image/jpeg', 'image/gif', 'image/webp',
        'application/pdf', 'text/plain', 'text/csv',
    ];

    let currentConversationReference = null;
    let isWaiting = false;
    let greetingShown = false;
    let pendingAttachments = [];

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

    const footerEl = panel.querySelector('.backoffice-assistant__footer');

    function showHistoriesView() {
        historiesEl.hidden = false;
        messagesEl.classList.add('backoffice-assistant__messages--hidden');
        footerEl.hidden = true;
        inputEl.disabled = true;
        sendBtn.disabled = true;
        loadHistories();
    }

    function hideHistoriesView() {
        historiesEl.hidden = true;
        messagesEl.classList.remove('backoffice-assistant__messages--hidden');
        footerEl.hidden = false;
        inputEl.disabled = false;
        sendBtn.disabled = false;
    }

    function startNewConversation() {
        currentConversationReference = null;
        agentBadgeEl.textContent = '';
        agentBadgeEl.classList.remove('backoffice-assistant__agent-badge--animate');
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

    function addRetryButton(prompt, attachments) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.classList.add('backoffice-assistant__retry-btn');
        btn.textContent = 'Retry';
        btn.addEventListener('click', function () {
            btn.remove();
            doSendMessage(prompt, attachments);
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

    function createStreamParser(onEvent) {
        var buffer = '';

        return function feed(chunk) {
            buffer += chunk;

            var boundary = buffer.indexOf('\n\n');

            while (boundary !== -1) {
                var block = buffer.substring(0, boundary);
                buffer = buffer.substring(boundary + 2);

                var lines = block.split('\n');

                for (var i = 0; i < lines.length; i++) {
                    if (lines[i].startsWith('data: ')) {
                        try {
                            onEvent(JSON.parse(lines[i].slice(6)));
                        } catch (e) {
                            // Skip malformed JSON
                        }
                    }
                }

                boundary = buffer.indexOf('\n\n');
            }
        };
    }

    function updateAgentBadge(agentName) {
        var previousName = agentBadgeEl.textContent;
        agentBadgeEl.textContent = agentName;
        agentBadgeEl.classList.remove('backoffice-assistant__agent-badge--animate');

        if (agentName !== previousName) {
            void agentBadgeEl.offsetWidth;
            agentBadgeEl.classList.add('backoffice-assistant__agent-badge--animate');
        }
    }

    function addReasoningMessage(text) {
        var bubble = document.createElement('div');
        bubble.classList.add('backoffice-assistant__message', 'backoffice-assistant__message--reasoning');
        bubble.textContent = text;
        messagesEl.appendChild(bubble);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        return bubble;
    }

    function addToolCallMessage(name, args, result) {
        var bubble = document.createElement('div');
        bubble.classList.add('backoffice-assistant__message', 'backoffice-assistant__message--tool-call');

        var label = document.createElement('div');
        label.classList.add('backoffice-assistant__tool-call-label');
        label.innerHTML = '<i class="fa fa-cog"></i> ' + name;
        bubble.appendChild(label);

        if (args && Object.keys(args).length > 0) {
            var argsEl = document.createElement('div');
            argsEl.classList.add('backoffice-assistant__tool-call-section');

            var argsLabel = document.createElement('span');
            argsLabel.classList.add('backoffice-assistant__tool-call-section-label');
            argsLabel.textContent = 'Arguments';
            argsEl.appendChild(argsLabel);

            var argsCode = document.createElement('pre');
            argsCode.classList.add('backoffice-assistant__tool-call-code');
            argsCode.textContent = JSON.stringify(args, null, 2);
            argsEl.appendChild(argsCode);

            bubble.appendChild(argsEl);
        }

        if (result) {
            var resultEl = document.createElement('div');
            resultEl.classList.add('backoffice-assistant__tool-call-section');

            var resultToggle = document.createElement('button');
            resultToggle.type = 'button';
            resultToggle.classList.add('backoffice-assistant__tool-call-toggle');
            resultToggle.textContent = 'Show result';
            resultEl.appendChild(resultToggle);

            var resultCode = document.createElement('pre');
            resultCode.classList.add('backoffice-assistant__tool-call-code', 'backoffice-assistant__tool-call-code--collapsed');

            try {
                resultCode.textContent = JSON.stringify(JSON.parse(result), null, 2);
            } catch (e) {
                resultCode.textContent = result;
            }

            resultEl.appendChild(resultCode);

            resultToggle.addEventListener('click', function () {
                var isCollapsed = resultCode.classList.toggle('backoffice-assistant__tool-call-code--collapsed');
                resultToggle.textContent = isCollapsed ? 'Show result' : 'Hide result';
            });

            bubble.appendChild(resultEl);
        }

        messagesEl.appendChild(bubble);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        return bubble;
    }

    function setWaiting(waiting) {
        isWaiting = waiting;
        sendBtn.disabled = waiting;
        inputEl.disabled = waiting;
        attachBtn.disabled = waiting;
    }

    function handleSseEvent(data, prompt, loadingEl, attachments) {
        switch (data.type) {
            case 'agent_selected':
                updateAgentBadge(data.agent);

                if (data.conversation_reference) {
                    currentConversationReference = data.conversation_reference;
                    saveState();
                }

                break;
            case 'reasoning':
                addReasoningMessage(data.message);

                break;
            case 'tool_call':
                addToolCallMessage(data.name, data.arguments, data.result);

                break;
            case 'ai_response':
                loadingEl.remove();
                addMessage('ai', data.message || '');

                if (data.conversation_reference) {
                    currentConversationReference = data.conversation_reference;
                    saveState();
                }

                break;
            case 'error':
                loadingEl.remove();
                addMessage('ai', 'Error: ' + data.message);
                addRetryButton(prompt, attachments);

                break;
            default:
                // Backwards compatibility for old format
                if (data.error) {
                    loadingEl.remove();
                    addMessage('ai', 'Error: ' + data.error);
                    addRetryButton(prompt, attachments);
                } else if (data.ai_response) {
                    loadingEl.remove();
                    currentConversationReference = data.conversation_reference;
                    saveState();
                    addMessage('ai', data.ai_response);
                }
        }
    }

    function doSendMessage(prompt, attachments) {
        var bubble = addMessage('user', prompt);

        if (attachments && attachments.length > 0) {
            addAttachmentPills(bubble, attachments);
        }

        setWaiting(true);

        const loadingEl = addLoadingIndicator();
        const body = { prompt: prompt, context: { current_page: getBreadcrumb() } };

        if (currentConversationReference) {
            body.conversation_reference = currentConversationReference;
        }

        if (attachments && attachments.length > 0) {
            body.attachments = attachments.map(function (a) {
                return { content: a.content, mediaType: a.mediaType };
            });
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
                if (!response.ok) {
                    throw new Error('Request failed with status ' + response.status);
                }

                var reader = response.body.getReader();
                var decoder = new TextDecoder();
                var receivedEvents = false;

                var feed = createStreamParser(function (data) {
                    receivedEvents = true;
                    handleSseEvent(data, prompt, loadingEl, attachments);
                });

                function readChunk() {
                    return reader.read().then(function (result) {
                        if (result.done) {
                            loadingEl.remove();

                            if (!receivedEvents) {
                                addMessage('ai', 'No response received.');
                                addRetryButton(prompt, attachments);
                            }

                            return;
                        }

                        feed(decoder.decode(result.value, { stream: true }));

                        return readChunk();
                    });
                }

                return readChunk();
            })
            .catch(function () {
                loadingEl.remove();
                addMessage('ai', 'Connection error. Please try again.');
                addRetryButton(prompt, attachments);
            })
            .finally(function () {
                setWaiting(false);
                inputEl.focus();
            });
    }

    function sendMessage() {
        const prompt = inputEl.value.trim();

        if (!prompt || isWaiting) {
            return;
        }

        var attachmentsSnapshot = pendingAttachments.slice();
        clearPendingAttachments();
        inputEl.value = '';
        resizeInput();
        doSendMessage(prompt, attachmentsSnapshot);
    }

    function deleteConversation(conversationReference, listItem) {
        // Disable all interactions on the row immediately
        listItem.style.pointerEvents = 'none';
        listItem.classList.add('backoffice-assistant__histories-item--deleting');

        fetch('/backoffice-assistant/conversation-histories/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ conversation_reference: conversationReference }),
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Delete failed');
                }

                listItem.addEventListener('transitionend', function () {
                    listItem.remove();

                    if (historiesList.children.length === 0) {
                        historiesEmpty.hidden = false;
                    }

                    if (currentConversationReference === conversationReference) {
                        startNewConversation();
                        showHistoriesView();
                    }
                }, { once: true });

                listItem.classList.add('backoffice-assistant__histories-item--deleted');
            })
            .catch(function () {
                listItem.style.pointerEvents = '';
                listItem.classList.remove('backoffice-assistant__histories-item--deleting');
            });
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
                    var li = document.createElement('li');
                    li.classList.add('backoffice-assistant__histories-item');

                    var nameSpan = document.createElement('span');
                    nameSpan.classList.add('backoffice-assistant__histories-item-name');
                    nameSpan.textContent = entry.name || entry.conversation_reference;
                    li.appendChild(nameSpan);

                    if (entry.agent) {
                        var agentSpan = document.createElement('span');
                        agentSpan.classList.add('backoffice-assistant__histories-item-agent');
                        agentSpan.textContent = entry.agent;
                        li.appendChild(agentSpan);
                    }

                    var deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.classList.add('backoffice-assistant__histories-item-delete');
                    deleteBtn.title = 'Delete conversation';
                    deleteBtn.innerHTML = '<i class="fa fa-trash"></i>';
                    deleteBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        deleteConversation(entry.conversation_reference, li);
                    });
                    li.appendChild(deleteBtn);

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
                if (data.agent) {
                    updateAgentBadge(data.agent);
                }

                var messages = Array.isArray(data.messages) ? data.messages : [];
                messages.forEach(function (msg) {
                    if (msg.type === 'user') {
                        addMessage('user', msg.content || '');
                    } else if (msg.type === 'tool_call') {
                        addToolCallMessage(msg.content || 'tool', null, null);
                    } else if (msg.type === 'tool_result') {
                        addToolCallMessage('Tool Result', null, msg.content || '');
                    } else {
                        addMessage('ai', msg.content || '');
                    }
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

    function handleFileSelect(files) {
        for (var i = 0; i < files.length; i++) {
            var file = files[i];

            if (ALLOWED_TYPES.indexOf(file.type) === -1) {
                addMessage('ai', 'Unsupported file type: ' + file.name);

                continue;
            }

            if (file.size > MAX_FILE_SIZE) {
                addMessage('ai', 'File too large (max 5 MB): ' + file.name);

                continue;
            }

            readFileAsBase64(file);
        }

        fileInput.value = '';
    }

    function readFileAsBase64(file) {
        var reader = new FileReader();

        reader.onload = function (event) {
            var base64 = event.target.result.split(',')[1];

            pendingAttachments.push({
                name: file.name,
                content: base64,
                mediaType: file.type,
            });

            renderAttachmentChip(file.name, pendingAttachments.length - 1);
        };

        reader.readAsDataURL(file);
    }

    function renderAttachmentChip(fileName, index) {
        var chip = document.createElement('div');
        chip.classList.add('backoffice-assistant__attachment-chip');
        chip.dataset.index = index;

        var nameSpan = document.createElement('span');
        nameSpan.classList.add('backoffice-assistant__attachment-chip-name');
        nameSpan.textContent = fileName.length > 20 ? fileName.substring(0, 17) + '...' : fileName;
        nameSpan.title = fileName;
        chip.appendChild(nameSpan);

        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.classList.add('backoffice-assistant__attachment-chip-remove');
        removeBtn.innerHTML = '<i class="fa fa-times"></i>';
        removeBtn.addEventListener('click', function () {
            var idx = parseInt(chip.dataset.index, 10);
            pendingAttachments.splice(idx, 1);
            chip.remove();
            reindexChips();
        });
        chip.appendChild(removeBtn);

        attachmentsPreview.appendChild(chip);
    }

    function reindexChips() {
        var chips = attachmentsPreview.querySelectorAll('.backoffice-assistant__attachment-chip');

        for (var i = 0; i < chips.length; i++) {
            chips[i].dataset.index = i;
        }
    }

    function clearPendingAttachments() {
        pendingAttachments = [];
        attachmentsPreview.innerHTML = '';
    }

    function addAttachmentPills(bubble, attachments) {
        var pillsContainer = document.createElement('div');
        pillsContainer.classList.add('backoffice-assistant__message-attachments');

        for (var i = 0; i < attachments.length; i++) {
            var pill = document.createElement('span');
            pill.classList.add('backoffice-assistant__message-attachment-pill');
            pill.innerHTML = '<i class="fa fa-file"></i> ';
            pill.appendChild(document.createTextNode(attachments[i].name));
            pillsContainer.appendChild(pill);
        }

        bubble.appendChild(pillsContainer);
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

    attachBtn.addEventListener('click', function () {
        fileInput.click();
    });

    fileInput.addEventListener('change', function (event) {
        handleFileSelect(event.target.files);
    });

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
