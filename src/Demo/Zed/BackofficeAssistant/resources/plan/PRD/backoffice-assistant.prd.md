# PRD: Backoffice AI Assistant — Backend Endpoints (Step 1)

**Ticket:** https://spryker.atlassian.net/browse/CC-37815
**Module:** `BackofficeAssistant`
**Layer:** Zed
**Date:** 2026-03-12
**Status:** Draft

---

## Background

Spryker backoffice users spend significant time navigating complex workflows, locating data, and performing repetitive tasks. The Backoffice AI Assistant introduces a conversational AI layer directly into the Zed admin panel, allowing all authenticated users to interact with an AI model through a structured API.

This is **step 1** of a multi-phase initiative. The goal of this step is to establish the foundational backend infrastructure: three AJAX-ready endpoints that handle conversation lifecycle management (list, retrieve, prompt), backed by the `AiFoundation` module and PHP/Redis session storage. No frontend UI or AI tools are included in this phase.

Future phases will introduce multi-agent mode, tool invocations, and persistent conversation history.

---

## Goals

1. Expose three secured backoffice AJAX endpoints (`GET conversation-histories`, `GET conversation-histories/{ref}`, `POST prompt`) accessible only to authenticated Zed users.
2. Integrate `AiFoundationFacade::prompt()` as the execution engine for all AI interactions.
3. Persist conversation references and aliases in the user session (`conversation_histories` key) without any direct database writes from the `BackofficeAssistant` module.
4. Enable conversation continuity by allowing the `POST /prompt` endpoint to continue an existing session-stored conversation or transparently create a new one.
5. Generate `conversation_reference` values server-side using the format `customer_reference:timestamp:random_string`.

---

## User Stories & Acceptance Criteria

### Story 1: List conversation histories

> **As a** logged-in backoffice user,
> **I want** to retrieve a list of my active conversation sessions,
> **so that** I can see which AI conversations I've started and resume them by reference.

**Criterion 1.1** — Unauthenticated access is rejected

```
Scenario: Unauthenticated request is rejected
  Given I am not logged in to the backoffice
  When I send a GET request to /backoffice-assistant/conversation-histories
  Then I receive a redirect to the login page with HTTP 302
  And the response body contains no conversation data
```

**Criterion 1.2** — Returns conversations from session

```
Scenario: Authenticated user with existing conversations retrieves list
  Given I am logged in to the backoffice
  And my session contains 2 conversation entries under "conversation_histories"
  When I send a GET request to /backoffice-assistant/conversation-histories
  Then I receive HTTP 200
  And the response body is a JSON array with exactly 2 entries
  And each entry contains "conversation_reference" and "name" fields
```

**Criterion 1.3** — Empty list when no conversations

```
Scenario: Authenticated user with no conversations receives empty list
  Given I am logged in to the backoffice
  And my session contains no entries under "conversation_histories"
  When I send a GET request to /backoffice-assistant/conversation-histories
  Then I receive HTTP 200
  And the response body is an empty JSON array
```

---

### Story 2: Retrieve conversation by reference

> **As a** logged-in backoffice user,
> **I want** to retrieve the full message history of a specific conversation by its reference,
> **so that** I can review the complete AI exchange for that session.

**Criterion 2.1** — Retrieve owned conversation history

```
Scenario: User retrieves an existing conversation they own
  Given I am logged in to the backoffice
  And my session contains a conversation with reference "user123:1710000000:abc"
  When I send GET /backoffice-assistant/conversation-histories/user123:1710000000:abc
  Then I receive HTTP 200
  And the response body contains the full message history from AiFoundationFacade
```

**Criterion 2.2** — 404 for reference not in session

```
Scenario: User requests a conversation reference not in their session
  Given I am logged in to the backoffice
  And my session does not contain reference "other-user:1710000000:xyz"
  When I send GET /backoffice-assistant/conversation-histories/other-user:1710000000:xyz
  Then I receive HTTP 404
  And the response body contains an error message "Conversation not found"
```

---

### Story 3: Send a prompt / manage conversation lifecycle

> **As a** logged-in backoffice user,
> **I want** to send a prompt to the AI assistant (starting a new conversation or continuing an existing one),
> **so that** I receive an AI-generated response and the conversation is tracked in my session automatically.

**Criterion 3.1** — New conversation on first prompt

```
Scenario: User sends a prompt without a conversation reference (new conversation)
  Given I am logged in to the backoffice
  And I POST to /backoffice-assistant/prompt with body {"prompt": "What is the total orders today?"}
  Then I receive HTTP 200
  And the response contains an "ai_response" field with the AI-generated text
  And a new entry is added to my session under "conversation_histories"
  And the new entry "name" is the first 150 characters of the prompt
  And the new entry "conversation_reference" follows format "customer_reference:timestamp:random_string"
```

**Criterion 3.2** — Continue existing conversation

```
Scenario: User continues an existing conversation
  Given I am logged in to the backoffice
  And my session contains a conversation with reference "user123:1710000000:abc"
  When I POST to /backoffice-assistant/prompt with body {"conversation_reference": "user123:1710000000:abc", "prompt": "Tell me more"}
  Then I receive HTTP 200
  And the response contains an "ai_response" field
  And no new entry is added to "conversation_histories" in my session
  And the existing conversation reference is used to call AiFoundationFacade
```

**Criterion 3.3** — Invalid reference falls back to new conversation

```
Scenario: User provides a conversation reference that is not in their session
  Given I am logged in to the backoffice
  And my session does not contain reference "other:9999:xyz"
  When I POST to /backoffice-assistant/prompt with body {"conversation_reference": "other:9999:xyz", "prompt": "Hello"}
  Then a new conversation is created as if no reference was provided
  And the new "conversation_reference" is generated server-side
  And the new entry is saved to "conversation_histories" in my session
```

---

## Non-Functional Requirements

| Category | Requirement |
|---|---|
| **Security** | All 3 endpoints MUST be protected by Zed authentication; unauthenticated requests redirect to login |
| **Security** | Conversation references are validated against the current user's session only — cross-user access is not possible |
| **Performance** | Each endpoint MUST respond within 500ms excluding AI model latency |
| **Reliability** | If `AiFoundationFacade::prompt()` fails, the endpoint returns HTTP 500 with a generic error; exceptions are logged but not propagated to the client |
| **Scalability** | Session storage is the only persistence mechanism in `BackofficeAssistant`; no direct database writes |
| **Compatibility** | All responses use JSON format to support AJAX consumers |

---

## Success Metrics

| Metric | Measurement |
|---|---|
| All 3 endpoints return correct HTTP codes for auth/valid/invalid requests | Manual testing via backoffice endpoints |
| New conversation is created and persisted to session on first prompt | Manual inspection of session state |
| Existing conversation is continued when a valid session reference is provided | Manual: two sequential prompts share same reference |
| `BackofficeAssistant` module itself performs no direct DB writes | Code review; AiFoundation may write internally |

---

## Out of Scope

- Frontend UI (chat widget, conversation history page)
- Multi-agent mode (planned for next step per spec)
- AI tool invocations
- Persistent database storage for conversation histories in `BackofficeAssistant`
- Pagination or sorting of conversation list
- Conversation deletion or archiving

---

## Dependencies

- `AiFoundation` module with `AiFoundationFacadeInterface::prompt()` implemented
- Zed authentication/session infrastructure (Spryker core)

---

## Quality Gates

```bash
# Static analysis
sh .claude/bash-local/validation.sh

# Transfer generation
docker/sdk cli console transfer:generate
```
