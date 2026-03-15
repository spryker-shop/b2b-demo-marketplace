'use strict';

const BACKOFFICE_ASSISTANT_STORAGE_KEY = 'backoffice_assistant_state';
const BACKOFFICE_ASSISTANT_MAX_FILE_SIZE = 5 * 1024 * 1024;
const BACKOFFICE_ASSISTANT_ALLOWED_TYPES = [
    'image/png', 'image/jpeg', 'image/gif', 'image/webp',
    'application/pdf', 'text/plain', 'text/csv',
];
const BACKOFFICE_ASSISTANT_SELECTORS = {
    toggle: '.js-backoffice-assistant__toggle',
    panel: '.js-backoffice-assistant__panel',
    close: '.js-backoffice-assistant__close',
    historyBtn: '.js-backoffice-assistant__history-btn',
    newChat: '.js-backoffice-assistant__new-chat',
    messages: '.js-backoffice-assistant__messages',
    histories: '.js-backoffice-assistant__histories',
    historiesList: '.js-backoffice-assistant__histories-list',
    historiesEmpty: '.js-backoffice-assistant__histories-empty',
    input: '.js-backoffice-assistant__input',
    send: '.js-backoffice-assistant__send',
    agentBadge: '.js-backoffice-assistant__agent-badge',
    agentSelect: '.js-backoffice-assistant__agent-select',
    attach: '.js-backoffice-assistant__attach',
    fileInput: '.js-backoffice-assistant__file-input',
    attachmentsPreview: '.js-backoffice-assistant__attachments-preview',
    footer: '.backoffice-assistant__footer',
};
const BACKOFFICE_ASSISTANT_ENDPOINTS = {
    prompt: '/backoffice-assistant/prompt/index',
    histories: '/backoffice-assistant/conversation-histories/index',
    detail: '/backoffice-assistant/conversation-histories/detail',
    delete: '/backoffice-assistant/conversation-histories/delete',
};

// --- State Manager ---

function BackofficeAssistantState() {
    this.conversationReference = null;
    this.isWaiting = false;
    this.greetingShown = false;
    this.pendingAttachments = [];
}

BackofficeAssistantState.prototype.save = function (isOpen) {
    try {
        localStorage.setItem(BACKOFFICE_ASSISTANT_STORAGE_KEY, JSON.stringify({
            isOpen: isOpen,
            conversationReference: this.conversationReference,
        }));
    } catch (e) {
        // localStorage may be unavailable
    }
};

BackofficeAssistantState.prototype.load = function () {
    try {
        const raw = localStorage.getItem(BACKOFFICE_ASSISTANT_STORAGE_KEY);

        return raw ? JSON.parse(raw) : null;
    } catch (e) {
        return null;
    }
};

BackofficeAssistantState.prototype.reset = function () {
    this.conversationReference = null;
    this.greetingShown = false;
    this.pendingAttachments = [];
};

// --- API Client ---

function BackofficeAssistantApi() {}

BackofficeAssistantApi.prototype.fetchHistories = function () {
    return fetch(BACKOFFICE_ASSISTANT_ENDPOINTS.histories, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).then(function (response) {
        return response.json();
    });
};

BackofficeAssistantApi.prototype.fetchConversationDetail = function (conversationReference) {
    const url = BACKOFFICE_ASSISTANT_ENDPOINTS.detail +
        '?conversationReference=' + encodeURIComponent(conversationReference);

    return fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).then(function (response) {
        return response.json();
    });
};

BackofficeAssistantApi.prototype.deleteConversation = function (conversationReference) {
    return fetch(BACKOFFICE_ASSISTANT_ENDPOINTS.delete, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ conversation_reference: conversationReference }),
    }).then(function (response) {
        if (!response.ok) {
            throw new Error('Delete failed');
        }
    });
};

BackofficeAssistantApi.prototype.sendPrompt = function (body) {
    return fetch(BACKOFFICE_ASSISTANT_ENDPOINTS.prompt, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
    });
};

// --- SSE Stream Parser ---

function BackofficeAssistantStreamParser(onEvent) {
    this.buffer = '';
    this.onEvent = onEvent;
}

BackofficeAssistantStreamParser.prototype.feed = function (chunk) {
    this.buffer += chunk;

    let boundary = this.buffer.indexOf('\n\n');

    while (boundary !== -1) {
        const block = this.buffer.substring(0, boundary);
        this.buffer = this.buffer.substring(boundary + 2);

        const lines = block.split('\n');

        for (let i = 0; i < lines.length; i++) {
            if (lines[i].startsWith('data: ')) {
                try {
                    this.onEvent(JSON.parse(lines[i].slice(6)));
                } catch (e) {
                    // Skip malformed JSON
                }
            }
        }

        boundary = this.buffer.indexOf('\n\n');
    }
};

// --- Message Renderer ---

function BackofficeAssistantMessageRenderer(messagesEl) {
    this.messagesEl = messagesEl;
}

BackofficeAssistantMessageRenderer.prototype.scrollToBottom = function () {
    this.messagesEl.scrollTop = this.messagesEl.scrollHeight;
};

BackofficeAssistantMessageRenderer.prototype.addMessage = function (role, text) {
    const bubble = document.createElement('div');
    bubble.classList.add('backoffice-assistant__message', 'backoffice-assistant__message--' + role);
    bubble.textContent = text;
    this.messagesEl.appendChild(bubble);
    this.scrollToBottom();

    return bubble;
};

BackofficeAssistantMessageRenderer.prototype.addLoadingIndicator = function () {
    const el = document.createElement('div');
    el.classList.add(
        'backoffice-assistant__message',
        'backoffice-assistant__message--ai',
        'backoffice-assistant__message--loading',
    );
    el.innerHTML =
        '<span class="backoffice-assistant__typing-dot"></span>' +
        '<span class="backoffice-assistant__typing-dot"></span>' +
        '<span class="backoffice-assistant__typing-dot"></span>';
    this.messagesEl.appendChild(el);
    this.scrollToBottom();

    return el;
};

BackofficeAssistantMessageRenderer.prototype.addRetryButton = function (onRetry) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.classList.add('backoffice-assistant__retry-btn');
    btn.textContent = 'Retry';
    btn.addEventListener('click', function () {
        btn.remove();
        onRetry();
    });
    this.messagesEl.appendChild(btn);
    this.scrollToBottom();
};

BackofficeAssistantMessageRenderer.prototype.addReasoningMessage = function (text) {
    const bubble = document.createElement('div');
    bubble.classList.add('backoffice-assistant__message', 'backoffice-assistant__message--reasoning');
    bubble.textContent = text;
    this.messagesEl.appendChild(bubble);
    this.scrollToBottom();

    return bubble;
};

BackofficeAssistantMessageRenderer.prototype.addToolCallMessage = function (name, args, result) {
    const bubble = document.createElement('div');
    bubble.classList.add('backoffice-assistant__message', 'backoffice-assistant__message--tool-call');

    bubble.appendChild(this.createToolCallLabel(name));

    if (args && Object.keys(args).length > 0) {
        bubble.appendChild(this.createToolCallArgs(args));
    }

    if (result) {
        bubble.appendChild(this.createToolCallResult(result));
    }

    this.messagesEl.appendChild(bubble);
    this.scrollToBottom();

    return bubble;
};

BackofficeAssistantMessageRenderer.prototype.createToolCallLabel = function (name) {
    const label = document.createElement('div');
    label.classList.add('backoffice-assistant__tool-call-label');
    label.innerHTML = '<i class="fa fa-cog"></i> ' + name;

    return label;
};

BackofficeAssistantMessageRenderer.prototype.createToolCallArgs = function (args) {
    const section = document.createElement('div');
    section.classList.add('backoffice-assistant__tool-call-section');

    const sectionLabel = document.createElement('span');
    sectionLabel.classList.add('backoffice-assistant__tool-call-section-label');
    sectionLabel.textContent = 'Arguments';
    section.appendChild(sectionLabel);

    const code = document.createElement('pre');
    code.classList.add('backoffice-assistant__tool-call-code');
    code.textContent = JSON.stringify(args, null, 2);
    section.appendChild(code);

    return section;
};

BackofficeAssistantMessageRenderer.prototype.createToolCallResult = function (result) {
    const section = document.createElement('div');
    section.classList.add('backoffice-assistant__tool-call-section');

    const toggleBtn = document.createElement('button');
    toggleBtn.type = 'button';
    toggleBtn.classList.add('backoffice-assistant__tool-call-toggle');
    toggleBtn.textContent = 'Show result';
    section.appendChild(toggleBtn);

    const code = document.createElement('pre');
    code.classList.add('backoffice-assistant__tool-call-code', 'backoffice-assistant__tool-call-code--collapsed');

    try {
        code.textContent = JSON.stringify(JSON.parse(result), null, 2);
    } catch (e) {
        code.textContent = result;
    }

    section.appendChild(code);

    toggleBtn.addEventListener('click', function () {
        const isCollapsed = code.classList.toggle('backoffice-assistant__tool-call-code--collapsed');
        toggleBtn.textContent = isCollapsed ? 'Show result' : 'Hide result';
    });

    return section;
};

BackofficeAssistantMessageRenderer.prototype.addAttachmentPills = function (bubble, attachments) {
    const container = document.createElement('div');
    container.classList.add('backoffice-assistant__message-attachments');

    for (let i = 0; i < attachments.length; i++) {
        const pill = document.createElement('span');
        pill.classList.add('backoffice-assistant__message-attachment-pill');
        pill.innerHTML = '<i class="fa fa-file"></i> ';
        pill.appendChild(document.createTextNode(attachments[i].name));
        container.appendChild(pill);
    }

    bubble.appendChild(container);
};

BackofficeAssistantMessageRenderer.prototype.clear = function () {
    this.messagesEl.innerHTML = '';
};

// --- Attachment Manager ---

function BackofficeAssistantAttachmentManager(previewEl, fileInputEl, state, renderer) {
    this.previewEl = previewEl;
    this.fileInputEl = fileInputEl;
    this.state = state;
    this.renderer = renderer;
}

BackofficeAssistantAttachmentManager.prototype.handleFileSelect = function (files) {
    for (let i = 0; i < files.length; i++) {
        this.processFile(files[i]);
    }

    this.fileInputEl.value = '';
};

BackofficeAssistantAttachmentManager.prototype.processFile = function (file) {
    if (BACKOFFICE_ASSISTANT_ALLOWED_TYPES.indexOf(file.type) === -1) {
        this.renderer.addMessage('ai', 'Unsupported file type: ' + file.name);

        return;
    }

    if (file.size > BACKOFFICE_ASSISTANT_MAX_FILE_SIZE) {
        this.renderer.addMessage('ai', 'File too large (max 5 MB): ' + file.name);

        return;
    }

    this.readAsBase64(file);
};

BackofficeAssistantAttachmentManager.prototype.readAsBase64 = function (file) {
    const self = this;
    const reader = new FileReader();

    reader.onload = function (event) {
        const base64 = event.target.result.split(',')[1];

        self.state.pendingAttachments.push({
            name: file.name,
            content: base64,
            mediaType: file.type,
        });

        self.renderChip(file.name, self.state.pendingAttachments.length - 1);
    };

    reader.readAsDataURL(file);
};

BackofficeAssistantAttachmentManager.prototype.renderChip = function (fileName, index) {
    const self = this;
    const chip = document.createElement('div');
    chip.classList.add('backoffice-assistant__attachment-chip');
    chip.dataset.index = String(index);

    const nameSpan = document.createElement('span');
    nameSpan.classList.add('backoffice-assistant__attachment-chip-name');
    nameSpan.textContent = fileName.length > 20 ? fileName.substring(0, 17) + '...' : fileName;
    nameSpan.title = fileName;
    chip.appendChild(nameSpan);

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.classList.add('backoffice-assistant__attachment-chip-remove');
    removeBtn.innerHTML = '<i class="fa fa-times"></i>';
    removeBtn.addEventListener('click', function () {
        const idx = parseInt(chip.dataset.index, 10);
        self.state.pendingAttachments.splice(idx, 1);
        chip.remove();
        self.reindexChips();
    });
    chip.appendChild(removeBtn);

    this.previewEl.appendChild(chip);
};

BackofficeAssistantAttachmentManager.prototype.reindexChips = function () {
    const chips = this.previewEl.querySelectorAll('.backoffice-assistant__attachment-chip');

    for (let i = 0; i < chips.length; i++) {
        chips[i].dataset.index = String(i);
    }
};

BackofficeAssistantAttachmentManager.prototype.takeSnapshot = function () {
    const snapshot = this.state.pendingAttachments.slice();
    this.clear();

    return snapshot;
};

BackofficeAssistantAttachmentManager.prototype.clear = function () {
    this.state.pendingAttachments = [];
    this.previewEl.innerHTML = '';
};

// --- Agent Badge Manager ---

function BackofficeAssistantAgentBadge(badgeEl, selectEl) {
    this.badgeEl = badgeEl;
    this.selectEl = selectEl;
}

BackofficeAssistantAgentBadge.prototype.update = function (agentName) {
    const previousName = this.badgeEl.textContent;
    this.badgeEl.textContent = agentName;
    this.badgeEl.classList.remove('backoffice-assistant__agent-badge--animate');

    if (agentName !== previousName) {
        void this.badgeEl.offsetWidth;
        this.badgeEl.classList.add('backoffice-assistant__agent-badge--animate');
    }
};

BackofficeAssistantAgentBadge.prototype.reset = function () {
    this.badgeEl.textContent = '';
    this.badgeEl.classList.remove('backoffice-assistant__agent-badge--animate');

    if (this.selectEl) {
        this.selectEl.value = '';
    }
};

BackofficeAssistantAgentBadge.prototype.populateSelector = function (agentNames) {
    if (!this.selectEl) {
        return;
    }

    while (this.selectEl.options.length > 1) {
        this.selectEl.remove(1);
    }

    const selectEl = this.selectEl;

    agentNames.forEach(function (name) {
        const opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name;
        selectEl.appendChild(opt);
    });
};

BackofficeAssistantAgentBadge.prototype.getSelectedAgent = function () {
    return this.selectEl ? this.selectEl.value : '';
};

BackofficeAssistantAgentBadge.prototype.setSelectedAgent = function (value) {
    if (this.selectEl) {
        this.selectEl.value = value || '';
    }
};

// --- Histories Manager ---

function BackofficeAssistantHistories(elements, api, onSelect, onDeleteCurrent) {
    this.historiesEl = elements.histories;
    this.historiesList = elements.historiesList;
    this.historiesEmpty = elements.historiesEmpty;
    this.messagesEl = elements.messages;
    this.footerEl = elements.footer;
    this.inputEl = elements.input;
    this.sendBtn = elements.send;
    this.api = api;
    this.onSelect = onSelect;
    this.onDeleteCurrent = onDeleteCurrent;
}

BackofficeAssistantHistories.prototype.show = function () {
    this.historiesEl.hidden = false;
    this.messagesEl.classList.add('backoffice-assistant__messages--hidden');
    this.footerEl.hidden = true;
    this.inputEl.disabled = true;
    this.sendBtn.disabled = true;
    this.load();
};

BackofficeAssistantHistories.prototype.hide = function () {
    this.historiesEl.hidden = true;
    this.messagesEl.classList.remove('backoffice-assistant__messages--hidden');
    this.footerEl.hidden = false;
    this.inputEl.disabled = false;
    this.sendBtn.disabled = false;
};

BackofficeAssistantHistories.prototype.load = function () {
    const self = this;

    this.api.fetchHistories()
        .then(function (data) {
            const histories = data.histories || [];

            self.historiesList.innerHTML = '';

            if (histories.length === 0) {
                self.historiesEmpty.hidden = false;

                return;
            }

            self.historiesEmpty.hidden = true;
            histories.forEach(function (entry) {
                self.historiesList.appendChild(self.createHistoryItem(entry));
            });
        })
        .catch(function () {
            self.historiesEmpty.hidden = false;
            self.historiesEmpty.textContent = 'Failed to load conversations.';
        });
};

BackofficeAssistantHistories.prototype.createHistoryItem = function (entry) {
    const self = this;
    const li = document.createElement('li');
    li.classList.add('backoffice-assistant__histories-item');

    const nameSpan = document.createElement('span');
    nameSpan.classList.add('backoffice-assistant__histories-item-name');
    nameSpan.textContent = entry.name || entry.conversation_reference;
    li.appendChild(nameSpan);

    if (entry.agent) {
        const agentSpan = document.createElement('span');
        agentSpan.classList.add('backoffice-assistant__histories-item-agent');
        agentSpan.textContent = entry.agent;
        li.appendChild(agentSpan);
    }

    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.classList.add('backoffice-assistant__histories-item-delete');
    deleteBtn.title = 'Delete conversation';
    deleteBtn.innerHTML = '<i class="fa fa-trash"></i>';
    deleteBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        self.deleteItem(entry.conversation_reference, li);
    });
    li.appendChild(deleteBtn);

    li.addEventListener('click', function () {
        self.onSelect(entry.conversation_reference);
    });

    return li;
};

BackofficeAssistantHistories.prototype.deleteItem = function (conversationReference, listItem) {
    const self = this;

    listItem.style.pointerEvents = 'none';
    listItem.classList.add('backoffice-assistant__histories-item--deleting');

    this.api.deleteConversation(conversationReference)
        .then(function () {
            listItem.addEventListener('transitionend', function () {
                listItem.remove();

                if (self.historiesList.children.length === 0) {
                    self.historiesEmpty.hidden = false;
                }

                self.onDeleteCurrent(conversationReference);
            }, { once: true });

            listItem.classList.add('backoffice-assistant__histories-item--deleted');
        })
        .catch(function () {
            listItem.style.pointerEvents = '';
            listItem.classList.remove('backoffice-assistant__histories-item--deleting');
        });
};

// --- Main Controller ---

function BackofficeAssistant() {
    this.elements = this.resolveElements();

    if (!this.elements) {
        return;
    }

    this.state = new BackofficeAssistantState();
    this.api = new BackofficeAssistantApi();
    this.renderer = new BackofficeAssistantMessageRenderer(this.elements.messages);
    this.agentBadge = new BackofficeAssistantAgentBadge(this.elements.agentBadge, this.elements.agentSelect);
    this.attachments = new BackofficeAssistantAttachmentManager(
        this.elements.attachmentsPreview,
        this.elements.fileInput,
        this.state,
        this.renderer,
    );
    this.histories = new BackofficeAssistantHistories(
        this.elements,
        this.api,
        this.loadConversationDetail.bind(this),
        this.handleConversationDeleted.bind(this),
    );

    this.init();
}

BackofficeAssistant.prototype.resolveElements = function () {
    const toggle = document.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.toggle);
    const panel = document.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.panel);

    if (!toggle || !panel) {
        return null;
    }

    return {
        toggle: toggle,
        panel: panel,
        close: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.close),
        historyBtn: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.historyBtn),
        newChat: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.newChat),
        messages: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.messages),
        histories: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.histories),
        historiesList: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.historiesList),
        historiesEmpty: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.historiesEmpty),
        input: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.input),
        send: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.send),
        agentBadge: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.agentBadge),
        agentSelect: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.agentSelect),
        attach: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.attach),
        fileInput: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.fileInput),
        attachmentsPreview: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.attachmentsPreview),
        footer: panel.querySelector(BACKOFFICE_ASSISTANT_SELECTORS.footer),
    };
};

BackofficeAssistant.prototype.init = function () {
    this.loadAvailableAgents();
    this.restorePersistedState();
    this.bindEvents();
};

BackofficeAssistant.prototype.isPanelOpen = function () {
    return this.elements.panel.classList.contains('backoffice-assistant__panel--open');
};

BackofficeAssistant.prototype.openPanel = function () {
    this.elements.panel.classList.add('backoffice-assistant__panel--open');
    this.elements.toggle.hidden = true;
    this.loadAvailableAgents();
    this.state.save(true);

    if (!this.state.greetingShown && !this.state.conversationReference) {
        this.showGreeting();
    }
};

BackofficeAssistant.prototype.closePanel = function () {
    this.elements.panel.classList.remove('backoffice-assistant__panel--open');
    this.elements.toggle.hidden = false;
    this.state.save(false);
};

BackofficeAssistant.prototype.showGreeting = function () {
    const userName = (window.BackofficeAssistantConfig && window.BackofficeAssistantConfig.userName) || 'there';
    this.state.greetingShown = true;
    this.renderer.addMessage('ai', 'Hello, ' + userName + '! How can I help you today?');
};

BackofficeAssistant.prototype.loadAvailableAgents = function () {
    const self = this;

    this.api.fetchHistories()
        .then(function (data) {
            self.agentBadge.populateSelector(data.available_agents || []);
        })
        .catch(function () {
            // Silently fail
        });
};

BackofficeAssistant.prototype.startNewConversation = function () {
    this.state.reset();
    this.agentBadge.reset();
    this.renderer.clear();
    this.histories.hide();
    this.elements.input.value = '';
    this.elements.input.focus();
    this.showGreeting();
    this.state.save(true);
};

BackofficeAssistant.prototype.setWaiting = function (waiting) {
    this.state.isWaiting = waiting;
    this.elements.send.disabled = waiting;
    this.elements.input.disabled = waiting;
    this.elements.attach.disabled = waiting;
};

BackofficeAssistant.prototype.getBreadcrumb = function () {
    const breadcrumbEl = document.querySelector('.breadcrumb');

    if (breadcrumbEl) {
        return breadcrumbEl.textContent.trim().replace(/\s+/g, ' ');
    }

    return window.location.pathname;
};

BackofficeAssistant.prototype.sendMessage = function () {
    const prompt = this.elements.input.value.trim();

    if (!prompt || this.state.isWaiting) {
        return;
    }

    const attachmentsSnapshot = this.attachments.takeSnapshot();
    this.elements.input.value = '';
    this.resizeInput();
    this.doSendMessage(prompt, attachmentsSnapshot);
};

BackofficeAssistant.prototype.doSendMessage = function (prompt, messageAttachments) {
    const self = this;
    const bubble = this.renderer.addMessage('user', prompt);

    if (messageAttachments && messageAttachments.length > 0) {
        this.renderer.addAttachmentPills(bubble, messageAttachments);
    }

    this.setWaiting(true);

    const loadingEl = this.renderer.addLoadingIndicator();
    const body = {
        prompt: prompt,
        context: { current_page: this.getBreadcrumb() },
        selected_agent: this.agentBadge.getSelectedAgent(),
    };

    if (this.state.conversationReference) {
        body.conversation_reference = this.state.conversationReference;
    }

    if (messageAttachments && messageAttachments.length > 0) {
        body.attachments = messageAttachments.map(function (a) {
            return { content: a.content, mediaType: a.mediaType };
        });
    }

    this.api.sendPrompt(body)
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Request failed with status ' + response.status);
            }

            return self.readStream(response, prompt, loadingEl, messageAttachments);
        })
        .catch(function () {
            loadingEl.remove();
            self.renderer.addMessage('ai', 'Connection error. Please try again.');
            self.renderer.addRetryButton(function () {
                self.doSendMessage(prompt, messageAttachments);
            });
        })
        .finally(function () {
            self.setWaiting(false);
            self.elements.input.focus();
        });
};

BackofficeAssistant.prototype.readStream = function (response, prompt, loadingEl, messageAttachments) {
    const self = this;
    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let receivedEvents = false;

    const parser = new BackofficeAssistantStreamParser(function (data) {
        receivedEvents = true;
        self.handleSseEvent(data, prompt, loadingEl, messageAttachments);
    });

    function readChunk() {
        return reader.read().then(function (result) {
            if (result.done) {
                loadingEl.remove();

                if (!receivedEvents) {
                    self.renderer.addMessage('ai', 'No response received.');
                    self.renderer.addRetryButton(function () {
                        self.doSendMessage(prompt, messageAttachments);
                    });
                }

                return;
            }

            parser.feed(decoder.decode(result.value, { stream: true }));

            return readChunk();
        });
    }

    return readChunk();
};

BackofficeAssistant.prototype.handleSseEvent = function (data, prompt, loadingEl, messageAttachments) {
    const self = this;

    switch (data.type) {
        case 'agent_selected':
            this.agentBadge.update(data.agent);

            if (data.conversation_reference) {
                this.state.conversationReference = data.conversation_reference;
                this.state.save(true);
            }

            break;
        case 'reasoning':
            this.renderer.addReasoningMessage(data.message);

            break;
        case 'tool_call':
            this.renderer.addToolCallMessage(data.name, data.arguments, data.result);

            break;
        case 'ai_response':
            loadingEl.remove();
            this.renderer.addMessage('ai', data.message || '');

            if (data.conversation_reference) {
                this.state.conversationReference = data.conversation_reference;
                this.state.save(true);
            }

            break;
        case 'error':
            loadingEl.remove();
            this.renderer.addMessage('ai', 'Error: ' + data.message);
            this.renderer.addRetryButton(function () {
                self.doSendMessage(prompt, messageAttachments);
            });

            break;
        default:
            this.handleLegacySseEvent(data, prompt, loadingEl, messageAttachments);
    }
};

BackofficeAssistant.prototype.handleLegacySseEvent = function (data, prompt, loadingEl, messageAttachments) {
    const self = this;

    if (data.error) {
        loadingEl.remove();
        this.renderer.addMessage('ai', 'Error: ' + data.error);
        this.renderer.addRetryButton(function () {
            self.doSendMessage(prompt, messageAttachments);
        });

        return;
    }

    if (data.ai_response) {
        loadingEl.remove();
        this.state.conversationReference = data.conversation_reference;
        this.state.save(true);
        this.renderer.addMessage('ai', data.ai_response);
    }
};

BackofficeAssistant.prototype.loadConversationDetail = function (conversationReference) {
    const self = this;

    this.state.conversationReference = conversationReference;
    this.state.save(true);
    this.histories.hide();
    this.renderer.clear();

    this.api.fetchConversationDetail(conversationReference)
        .then(function (data) {
            if (data.agent) {
                self.agentBadge.update(data.agent);
            }

            self.agentBadge.setSelectedAgent(data.user_selected_agent);
            self.renderConversationMessages(data.messages);
            self.elements.input.focus();
        })
        .catch(function () {
            self.renderer.addMessage('ai', 'Failed to load conversation history.');
        });
};

BackofficeAssistant.prototype.renderConversationMessages = function (messages) {
    const list = Array.isArray(messages) ? messages : [];

    for (let i = 0; i < list.length; i++) {
        const msg = list[i];

        switch (msg.type) {
            case 'user':
                this.renderer.addMessage('user', msg.content || '');

                break;
            case 'tool_call':
                this.renderer.addToolCallMessage(msg.content || 'tool', null, null);

                break;
            case 'tool_result':
                this.renderer.addToolCallMessage('Tool Result', null, msg.content || '');

                break;
            default:
                this.renderer.addMessage('ai', msg.content || '');
        }
    }
};

BackofficeAssistant.prototype.handleConversationDeleted = function (conversationReference) {
    if (this.state.conversationReference === conversationReference) {
        this.startNewConversation();
        this.histories.show();
    }
};

BackofficeAssistant.prototype.resizeInput = function () {
    this.elements.input.style.height = 'auto';
    this.elements.input.style.height = Math.min(this.elements.input.scrollHeight, 120) + 'px';
};

BackofficeAssistant.prototype.restorePersistedState = function () {
    const savedState = this.state.load();

    if (!savedState) {
        return;
    }

    if (savedState.conversationReference) {
        this.state.conversationReference = savedState.conversationReference;
    }

    if (savedState.isOpen) {
        this.elements.panel.classList.add('backoffice-assistant__panel--open');
        this.elements.toggle.hidden = true;
        this.state.greetingShown = true;

        if (this.state.conversationReference) {
            this.loadConversationDetail(this.state.conversationReference);
        } else {
            this.showGreeting();
        }
    }
};

BackofficeAssistant.prototype.bindEvents = function () {
    const self = this;

    this.elements.toggle.addEventListener('click', function () {
        if (self.isPanelOpen()) {
            self.closePanel();
        } else {
            self.openPanel();
        }
    });

    this.elements.close.addEventListener('click', function () {
        self.closePanel();
    });

    this.elements.historyBtn.addEventListener('click', function () {
        self.histories.show();
    });

    this.elements.newChat.addEventListener('click', function () {
        self.startNewConversation();
    });

    this.elements.send.addEventListener('click', function () {
        self.sendMessage();
    });

    if (this.elements.agentSelect) {
        this.elements.agentSelect.addEventListener('change', function () {
            if (self.elements.agentSelect.value) {
                self.agentBadge.update(self.elements.agentSelect.value);
            }
        });
    }

    this.elements.attach.addEventListener('click', function () {
        self.elements.fileInput.click();
    });

    this.elements.fileInput.addEventListener('change', function (event) {
        self.attachments.handleFileSelect(event.target.files);
    });

    this.elements.input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            self.sendMessage();
        }
    });

    this.elements.input.addEventListener('input', function () {
        self.resizeInput();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && self.isPanelOpen()) {
            self.closePanel();
        }
    });
};

// --- Bootstrap ---

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        new BackofficeAssistant();
    });
} else {
    new BackofficeAssistant();
}
