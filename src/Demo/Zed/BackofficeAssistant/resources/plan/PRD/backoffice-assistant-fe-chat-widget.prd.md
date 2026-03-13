# Product Requirement Document: Backoffice AI Assistant — Frontend Chat Widget (Step 2)

**Module:** `BackofficeAssistant`
**Layer:** Zed (Presentation)
**Date:** 2026-03-12
**Status:** Draft
**Depends on:** [Step 1 PRD — Backend Endpoints](backoffice-assistant.prd.md)

---

## Background

With the backend endpoints established in Step 1, the Backoffice AI Assistant lacks a user-facing interface. Backoffice users currently have no way to interact with the AI assistant from within the Zed admin panel. Step 2 introduces a frontend chat widget — a persistent, overlay-based panel available on every backoffice page — that enables authenticated users to converse with the AI, manage multiple conversations, and receive contextually relevant responses based on their current navigation location.

This is an enhancement to the existing `BackofficeAssistant` module, adding a Twig-based frontend component that communicates with the Step 1 AJAX endpoints.

---

## Goals

1. Render a chat widget toggle button on every Zed Backoffice page, allowing users to open and close the assistant without page navigation.
2. Display a personalized greeting using the current user's name from session data on first interaction.
3. Detect and send the current backoffice navigation path as context with each prompt request.
4. Support multiple conversations — list existing conversations, switch between them, and resume chat history.
5. Allow users to start new conversations that are transparently created via the Step 1 POST /prompt endpoint.

---

## User Stories & Acceptance Criteria

### Story 1: Chat widget toggle on every page

> **As a** logged-in backoffice user,
> **I want** to see a chat widget toggle button on every Zed Backoffice page,
> **so that** I can open and close the AI assistant without navigating away from my current workflow.

**Criterion 1.1** — Chat widget button is visible on every page

```
Scenario: Chat toggle button appears on all backoffice pages
  Given I am logged in to the backoffice
  When I navigate to any backoffice page
  Then a chat toggle button is visible in a fixed position on the screen
  And the button does not overlap primary navigation or content areas
```

**Criterion 1.2** — Opening the chat widget

```
Scenario: User opens the chat widget
  Given I am logged in to the backoffice
  And the chat widget is closed
  When I click the chat toggle button
  Then the chat panel opens as an overlay on the right side of the screen
  And the chat panel displays within 200 milliseconds
  And the toggle button is hidden while the panel is open
```

**Criterion 1.3** — Closing the chat widget

```
Scenario: User closes the chat widget
  Given I am logged in to the backoffice
  And the chat widget is open
  When I click the close button on the chat panel
  Then the chat panel closes
  And the toggle button becomes visible again
  And no data is lost from the current conversation
```

**Criterion 1.4** — Chat state persists across page navigations

```
Scenario: Chat panel state is restored after page navigation
  Given I am logged in to the backoffice
  And the chat widget is open with an active conversation
  When I navigate to a different backoffice page
  Then the chat panel is automatically restored to its open state
  And the active conversation messages are reloaded from the backend
  And the same conversation_reference is used for subsequent messages
```

**Criterion 1.5** — Closed state persists across page navigations

```
Scenario: Closed chat state is preserved after page navigation
  Given I am logged in to the backoffice
  And the chat widget is closed
  When I navigate to a different backoffice page
  Then the chat panel remains closed
  And the toggle button is visible
```

---

### Story 2: Personalized greeting

> **As a** logged-in backoffice user,
> **I want** the AI assistant to greet me by name when I first open the chat,
> **so that** I have a personalized experience and know the assistant recognizes my identity.

**Criterion 2.1** — Greeting displays user's name

```
Scenario: Chat displays personalized greeting
  Given I am logged in to the backoffice as user "John Doe"
  And I have no active conversations
  When I open the chat widget for the first time
  Then the chat panel displays a greeting message containing "John Doe"
  And the greeting is generated client-side from the session user data
```

---

### Story 3: Current page context awareness

> **As a** logged-in backoffice user,
> **I want** the AI assistant to know which backoffice page I am currently on,
> **so that** it can provide contextually relevant responses based on my current navigation location.

**Criterion 3.1** — Current page context is sent with prompts

```
Scenario: Current page context is included in prompt requests
  Given I am logged in to the backoffice
  And I am on the "Orders" page
  When I send a prompt through the chat widget
  Then the POST request to /backoffice-assistant/prompt includes a "context" field
  And the "context" field contains the current navigation breadcrumb path
```

---

### Story 4: List and switch between conversations

> **As a** logged-in backoffice user,
> **I want** to see a list of my previous conversations and switch between them,
> **so that** I can resume any past AI conversation without losing history.

**Criterion 4.1** — Conversation list is displayed

```
Scenario: User views conversation list
  Given I am logged in to the backoffice
  And I have 3 existing conversations
  When I open the chat widget and access the conversation list
  Then I see a list of 3 conversations
  And each entry shows the conversation name
  And the list is fetched from GET /backoffice-assistant/conversation-histories
```

**Criterion 4.2** — User switches to an existing conversation

```
Scenario: User switches to a previous conversation
  Given I am logged in to the backoffice
  And the conversation list shows conversation "Order analysis"
  When I click on "Order analysis"
  Then the chat panel loads the full message history for that conversation
  And the history is fetched from GET /backoffice-assistant/conversation-histories/{reference}
  And the input field is ready for a new prompt in that conversation
```

**Criterion 4.3** — Empty state when no conversations exist

```
Scenario: User with no conversations sees empty state
  Given I am logged in to the backoffice
  And I have no existing conversations
  When I open the chat widget and access the conversation list
  Then I see an empty state message prompting me to start a new conversation
```

---

### Story 5: Start a new conversation

> **As a** logged-in backoffice user,
> **I want** to start a new conversation from the chat widget,
> **so that** I can begin a fresh AI interaction at any time.

**Criterion 5.1** — New conversation is started

```
Scenario: User starts a new conversation
  Given I am logged in to the backoffice
  And the chat widget is open
  When I click the "New conversation" button
  Then the chat panel clears any displayed messages
  And the input field is empty and focused
  And no conversation_reference is associated with the current chat until the first prompt is sent
```

**Criterion 5.2** — First message creates the conversation

```
Scenario: First message in a new conversation creates the session entry
  Given I am logged in to the backoffice
  And I have started a new conversation (no reference yet)
  When I type "Help me with product imports" and press send
  Then a POST request is sent to /backoffice-assistant/prompt without a conversation_reference
  And the response includes a new conversation_reference
  And the conversation appears in the conversation list with name "Help me with product imports"
```

---

### Story 6: Send messages and receive AI responses

> **As a** logged-in backoffice user,
> **I want** to send messages and receive AI responses within the chat widget,
> **so that** I can interact with the assistant in a conversational manner.

**Criterion 6.1** — User sends a message and receives a response

```
Scenario: User sends a message and sees AI response
  Given I am logged in to the backoffice
  And the chat widget is open with an active conversation
  When I type a message and press send
  Then my message appears in the chat as a user message
  And a loading indicator is displayed
  And the AI response appears in the chat within the time determined by backend latency
  And the loading indicator is removed
```

**Criterion 6.2** — Error handling for failed AI response

```
Scenario: AI backend returns an error
  Given I am logged in to the backoffice
  And the chat widget is open with an active conversation
  When I send a message and the backend returns HTTP 500
  Then the loading indicator is removed
  And an error message is displayed in the chat panel
  And I can retry sending the message
```

---

## Non-Functional Requirements

| Category | Requirement |
|---|---|
| **Performance** | Chat widget opens within 200 milliseconds of button click |
| **Performance** | Conversation list loads within 500 milliseconds |
| **Performance** | Message history for a conversation loads within 1 second |
| **Security** | All AJAX requests include CSRF token from the backoffice session |
| **Security** | No conversation content or user data is stored in localStorage — chat messages come from backend session |
| **UX** | localStorage stores only UI state: panel open/closed flag and current conversation_reference (non-sensitive routing key) |
| **Accessibility** | Chat widget is keyboard navigable (Tab, Enter, Escape to close) |
| **Compatibility** | Widget works in Chrome 90+, Firefox 90+, Safari 14+, Edge 90+ |
| **UX** | Chat state (open/closed) persists across page navigations within the same session |
| **UX** | Chat input field supports multiline text and send via Enter key (Shift+Enter for newline) |

---

## Success Metrics

| Metric | Measurement |
|---|---|
| Chat widget renders on 100% of backoffice pages | Manual testing across 5+ different page types |
| User greeting displays correct name from session | Manual test with 2+ different user accounts |
| Page context is correctly sent with each prompt | Browser DevTools network inspection |
| Conversation list shows all session conversations | Compare with GET /conversation-histories response |
| User can switch between conversations and see full history | Manual test with 3+ conversations |
| New conversation is created on first prompt when no reference exists | Network inspection + conversation list update |

---

## Out of Scope

- AI model configuration or prompt engineering
- Conversation deletion or archiving
- File upload or attachment support
- Voice input
- Markdown rendering in AI responses (plain text only for Step 2)
- Mobile-responsive design (Zed Backoffice is desktop-only)

---

## Dependencies

**Technical:**
- Step 1 backend endpoints (PRD: `backoffice-assistant.prd.md`) fully implemented
- Zed Backoffice layout Twig template accessible for widget injection

**Implementation Reference (informational only):**
- Example Yves chat-popup component at: `/Users/vitaliiivanov/Desktop/development/spryker/sc-b2b-mp-industry-demo/src/Pyz/Yves/ShopUi/Theme/default/components/molecules/chat-popup`
- The example is for Yves; adaptation needed for Zed

---

## Quality Gates

```bash
# Static analysis
sh .claude/bash-local/validation.sh

# Transfer generation (if transfers are modified)
docker/sdk cli console transfer:generate

# Frontend build verification
docker/sdk cli npm install && docker/sdk cli npm zed
```

---

## Priority Breakdown

**MUST:**
- Chat widget toggle on every backoffice page (Story 1)
- Send messages and receive AI responses (Story 6)
- Start a new conversation (Story 5)
- Page context sent with prompts (Story 3)

**SHOULD:**
- Personalized greeting by name (Story 2)
- List and switch between conversations (Story 4)

**COULD:**
- Keyboard shortcuts for opening/closing chat
- Chat panel resize/drag

**WON'T:**
- Markdown rendering (deferred to Step 3)
- Conversation deletion
- File attachments
- Mobile support
