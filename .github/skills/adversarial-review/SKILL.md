---
name: adversarial-review
description: >
  Orchestrate a multi-model adversarial review panel where Claude Sonnet 4.6, Claude Opus 4.6,
  GPT-5.4, and GPT-5.3 Codex each independently review submitted content, then critique each other's
  reviews, and finally synthesize a definitive prioritized list of improvements.
  Use this skill whenever the user asks for a review, critique, code review, feedback, editorial pass,
  or wants multiple expert perspectives on any content — including code, architecture plans, writing,
  API designs, configurations, documentation, strategies, or pull requests.
  Invoke even if the user doesn't say "adversarial" — any request for review, critique, or feedback
  on content they share qualifies.
---

# Adversarial Multi-Model Review

This skill runs a three-phase review pipeline across four AI models. Each model reviews
independently, then reviews the others' work, and finally one model synthesizes everything into a
definitive findings report.

## Models Used

| Alias | Model ID | Provider |
|-------|----------|----------|
| Sonnet 4.6 | `claude-sonnet-4-6` | Anthropic |
| Opus 4.6 | `claude-opus-4-6` | Anthropic |
| GPT-5.4 | `gpt-5.4` | OpenAI |
| GPT-5.3 Codex | `gpt-5.3-codex` | OpenAI |

> **Note:** If any model ID needs updating (API naming changes over time), edit `scripts/models_config.json`.

## How to Invoke

1. User shares content to review (pastes code, attaches file, or describes what to review)
2. Read any additional context from the user (e.g., "review this for security issues", "is this production-ready?")
3. Save content to a temp file if it's large, or pass inline
4. Run the review script (see below)
5. Present the final synthesis report to the user

---

## Step-by-Step Workflow

### 1. Gather Input

Extract from the conversation:
- **The content to review** — what the user shared (code, text, config, etc.)
- **The review focus** — what they care about. If not stated, infer from content type:
  - Code → correctness, performance, security, maintainability
  - Writing → clarity, structure, accuracy, tone
  - Architecture/plan → feasibility, risks, alternatives
  - Config/infra → correctness, security, best practices
- **Context** — any background the user provided about goals, constraints, or audience

### 2. Run the Review Script

```bash
python .github/skills/adversarial-review/scripts/run_adversarial_review.py \
  --content "<content or path to file>" \
  --context "<review focus and any user-provided context>" \
  --output /tmp/adversarial-review-output.json
```

Or pass a file:
```bash
python .github/skills/adversarial-review/scripts/run_adversarial_review.py \
  --content-file path/to/content.txt \
  --context "Review this for production readiness" \
  --output /tmp/adversarial-review-output.json
```

The script runs phases 1 and 2 concurrently where possible, then phase 3.

### 3. Present Results

Read `/tmp/adversarial-review-output.json` and present the `synthesis` field to the user.
The synthesis is structured as a full Markdown report — render it directly.

If the user wants to see individual model reviews, they're in the `reviews` and `cross_reviews` fields.

---

## What Each Phase Does

### Phase 1 — Independent Reviews (parallel, ~30s)

Each model receives only the original content and context. No model sees the others' work.
Each produces: Summary · Strengths · Issues & Weaknesses · Suggestions · Critical Concerns.

### Phase 2 — Cross-Reviews (parallel, ~30s)

Each model is shown the other three reviews plus its own, and asked to:
- Identify what it agrees with from the other reviews
- Challenge points it thinks are wrong or overstated
- Flag important issues the others missed
- Acknowledge valid points it missed in phase 1

This is the "adversarial" step — models actively critique each other.

### Phase 3 — Synthesis (~20s)

A fifth call (using Opus 4.6 for its reasoning depth) synthesizes all eight outputs
(4 reviews + 4 cross-reviews) into a final prioritized report:
- **Consensus Issues** — problems all/most models agreed on (high credibility)
- **High-Priority Suggestions** — ranked by impact, noting multi-model agreement
- **Contested Points** — where models disagreed, with synthesis judgment
- **Minor / Optional Improvements**
- **What's Working Well**

---

## Environment Setup

The script requires API keys in environment variables:
- `ANTHROPIC_API_KEY` — for Claude models
- `OPENAI_API_KEY` — for GPT models

If a model fails (API error, key missing), the script continues with available models and notes
which were skipped. A review with 3 models is still valuable.

Install dependencies if needed:
```bash
pip install anthropic openai
```

---

## Output Format

The JSON output file has this shape:
```json
{
  "content_summary": "First ~200 chars of reviewed content",
  "context": "The review focus",
  "reviews": {
    "claude-sonnet-4-6": "...",
    "claude-opus-4-6": "...",
    "gpt-5.4": "...",
    "gpt-5.3-codex": "..."
  },
  "cross_reviews": {
    "claude-sonnet-4-6": "...",
    "claude-opus-4-6": "...",
    "gpt-5.4": "...",
    "gpt-5.3-codex": "..."
  },
  "synthesis": "# Adversarial Review: Definitive Findings\n..."
}
```

---

## Handling Edge Cases

- **Very long content**: The script truncates at 12,000 tokens per model. If content is code, it keeps the full file and truncates comments. Warn the user if truncation occurred.
- **Only some models available**: Still run with what's available. 2+ models is the minimum for adversarial value.
- **User only wants Claude models**: Pass `--models claude` flag to restrict to Anthropic only.
- **User wants to see individual reviews**: After presenting the synthesis, offer to show individual model reviews on request.
