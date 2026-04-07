#!/usr/bin/env python3
"""
Adversarial Multi-Model Review Script

Runs content through four AI models in three phases:
  Phase 1: Independent reviews (parallel)
  Phase 2: Cross-reviews — each model critiques the others' reviews (parallel)
  Phase 3: Synthesis — one model consolidates all reviews into a definitive report
"""

import argparse
import asyncio
import json
import os
import sys
from pathlib import Path

# ---------------------------------------------------------------------------
# Prompt templates
# ---------------------------------------------------------------------------

REVIEW_PROMPT = """\
You are performing a thorough, honest review of the following content.
Be direct and critical. Your job is to find real issues, not to be polite.
Prioritise substance over style.

## Review Focus
{context}

## Content to Review
{content}

---

Respond using exactly this structure:

## Summary
2–3 sentence overall verdict.

## Strengths
- List genuine strengths (be brief; skip this section if there are none worth mentioning)

## Issues & Weaknesses
- List real problems with specifics — reference line numbers or exact phrases where relevant

## Suggestions for Improvement
Numbered, actionable, most important first.

## Critical Concerns
Any serious issues that must be addressed before this is acceptable.
(Omit this section if there are none.)
"""

CROSS_REVIEW_PROMPT = """\
You are reviewing three other AI models' critiques of the same content.
Your task is to stress-test their reviews: agree where they're right, push back where they're wrong,
and surface anything important that was missed.

## Review Focus
{context}

## Original Content That Was Reviewed
{content}

---

## Reviews From Other Models

{other_reviews}

---

## Your Own Previous Review
{own_review}

---

Respond using exactly this structure:

## Points of Agreement
What did the other models correctly identify that you also noticed?

## Points of Disagreement
What did other models get wrong, overstate, or frame poorly? Be specific.

## Missed Issues
Important problems you raised that none of the other models caught.

## New Insights From Their Reviews
Valid points the others raised that you underweighted or missed entirely.

## Verdict on Review Quality
Which of the other reviews was most accurate, and why?
"""

SYNTHESIS_PROMPT = """\
You are synthesizing four rounds of independent AI model reviews and four rounds of cross-reviews
into a single definitive improvement guide. Your output is the final authoritative word.

## Review Focus
{context}

## Original Content
{content}

---

## Independent Reviews (Phase 1)
{all_reviews}

---

## Cross-Reviews — Models Critiquing Each Other (Phase 2)
{all_cross_reviews}

---

Synthesize everything into a structured report using this exact format:

# Adversarial Review: Definitive Findings

## Executive Summary
What is this content? What is the overall verdict? (3–5 sentences)

## Consensus Issues
Problems that two or more models independently identified.
These are the highest-confidence findings.
(Indicate how many models raised each issue.)

## High-Priority Suggestions
Ranked list of the most impactful improvements.
For each item note: how many models raised it, and whether there was any cross-review disagreement.

## Contested Points
Issues where models disagreed. State both sides and give your synthesis judgment.

## Minor / Optional Improvements
Lower-priority polish items not worth blocking on.

## What's Working Well
Genuine strengths with broad model agreement.

---
Be direct. Ruthlessly prioritize. Cut padding.
"""

# ---------------------------------------------------------------------------
# API helpers
# ---------------------------------------------------------------------------

MAX_CONTENT_CHARS = 40_000  # ~10k tokens, safe for all models


def truncate_content(content: str) -> tuple[str, bool]:
    if len(content) <= MAX_CONTENT_CHARS:
        return content, False
    return content[:MAX_CONTENT_CHARS] + "\n\n[... content truncated ...]", True


async def call_anthropic(model_id: str, prompt: str) -> str:
    try:
        import anthropic
    except ImportError:
        raise RuntimeError("anthropic package not installed. Run: pip install anthropic")

    api_key = os.environ.get("ANTHROPIC_API_KEY")
    if not api_key:
        raise RuntimeError("ANTHROPIC_API_KEY environment variable not set")

    client = anthropic.AsyncAnthropic(api_key=api_key)
    message = await client.messages.create(
        model=model_id,
        max_tokens=4096,
        messages=[{"role": "user", "content": prompt}],
    )
    return message.content[0].text


async def call_openai(model_id: str, prompt: str) -> str:
    try:
        import openai
    except ImportError:
        raise RuntimeError("openai package not installed. Run: pip install openai")

    api_key = os.environ.get("OPENAI_API_KEY")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY environment variable not set")

    client = openai.AsyncOpenAI(api_key=api_key)
    response = await client.chat.completions.create(
        model=model_id,
        max_tokens=4096,
        messages=[{"role": "user", "content": prompt}],
    )
    return response.choices[0].message.content


async def call_model(provider: str, model_id: str, prompt: str) -> str:
    if provider == "anthropic":
        return await call_anthropic(model_id, prompt)
    elif provider == "openai":
        return await call_openai(model_id, prompt)
    else:
        raise ValueError(f"Unknown provider: {provider}")


# ---------------------------------------------------------------------------
# Review phases
# ---------------------------------------------------------------------------

async def run_phase1(models: list[dict], content: str, context: str) -> dict[str, str]:
    """Phase 1: Independent reviews in parallel."""
    print("Phase 1: Running independent reviews...", flush=True)

    prompt = REVIEW_PROMPT.format(context=context, content=content)

    async def review_one(model: dict) -> tuple[str, str | None]:
        alias = model["alias"]
        try:
            result = await call_model(model["provider"], model["model_id"], prompt)
            print(f"  ✓ {alias}", flush=True)
            return alias, result
        except Exception as exc:
            print(f"  ✗ {alias}: {exc}", flush=True)
            return alias, None

    results = await asyncio.gather(*[review_one(m) for m in models])
    return {alias: text for alias, text in results if text is not None}


async def run_phase2(
    models: list[dict],
    reviews: dict[str, str],
    content: str,
    context: str,
) -> dict[str, str]:
    """Phase 2: Each model cross-reviews the others' work in parallel."""
    print("Phase 2: Running cross-reviews...", flush=True)

    aliases_with_reviews = list(reviews.keys())

    async def cross_review_one(model: dict) -> tuple[str, str | None]:
        alias = model["alias"]
        if alias not in reviews:
            # Model failed in phase 1 — skip
            return alias, None

        other_reviews_text = "\n\n---\n\n".join(
            f"### {other_alias}\n{text}"
            for other_alias, text in reviews.items()
            if other_alias != alias
        )

        prompt = CROSS_REVIEW_PROMPT.format(
            context=context,
            content=content,
            other_reviews=other_reviews_text,
            own_review=reviews[alias],
        )

        try:
            result = await call_model(model["provider"], model["model_id"], prompt)
            print(f"  ✓ {alias}", flush=True)
            return alias, result
        except Exception as exc:
            print(f"  ✗ {alias}: {exc}", flush=True)
            return alias, None

    results = await asyncio.gather(*[cross_review_one(m) for m in models])
    return {alias: text for alias, text in results if text is not None}


async def run_phase3(
    synthesis_model: dict,
    reviews: dict[str, str],
    cross_reviews: dict[str, str],
    content: str,
    context: str,
) -> str:
    """Phase 3: Synthesize all reviews into a definitive report."""
    print(f"Phase 3: Synthesizing with {synthesis_model['alias']}...", flush=True)

    all_reviews_text = "\n\n---\n\n".join(
        f"### {alias}\n{text}" for alias, text in reviews.items()
    )
    all_cross_reviews_text = "\n\n---\n\n".join(
        f"### {alias}\n{text}" for alias, text in cross_reviews.items()
    )

    prompt = SYNTHESIS_PROMPT.format(
        context=context,
        content=content,
        all_reviews=all_reviews_text,
        all_cross_reviews=all_cross_reviews_text,
    )

    result = await call_model(
        synthesis_model["provider"], synthesis_model["model_id"], prompt
    )
    print("  ✓ Synthesis complete", flush=True)
    return result


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

async def main() -> None:
    parser = argparse.ArgumentParser(
        description="Run an adversarial multi-model review on any content."
    )
    content_group = parser.add_mutually_exclusive_group(required=True)
    content_group.add_argument("--content", help="Content to review (inline string)")
    content_group.add_argument("--content-file", help="Path to file containing content to review")
    parser.add_argument("--context", default="General review", help="Review focus / instructions")
    parser.add_argument(
        "--output",
        default="/tmp/adversarial-review-output.json",
        help="Path to write JSON output (default: /tmp/adversarial-review-output.json)",
    )
    parser.add_argument(
        "--models",
        default="all",
        choices=["all", "claude", "openai"],
        help="Which providers to use (default: all)",
    )
    parser.add_argument(
        "--config",
        default=str(Path(__file__).parent / "models_config.json"),
        help="Path to models_config.json",
    )
    args = parser.parse_args()

    # Load content
    if args.content_file:
        content_path = Path(args.content_file)
        if not content_path.exists():
            print(f"Error: content file not found: {args.content_file}", file=sys.stderr)
            sys.exit(1)
        content = content_path.read_text(encoding="utf-8")
    else:
        content = args.content

    content, was_truncated = truncate_content(content)
    if was_truncated:
        print(
            f"Warning: content was truncated to {MAX_CONTENT_CHARS} characters.",
            flush=True,
        )

    # Load model config
    config_path = Path(args.config)
    if not config_path.exists():
        print(f"Error: models config not found: {args.config}", file=sys.stderr)
        sys.exit(1)

    with config_path.open() as f:
        config = json.load(f)

    all_models: list[dict] = config["models"]
    synthesis_alias: str = config["synthesis_model"]

    # Filter by provider if requested
    if args.models == "claude":
        all_models = [m for m in all_models if m["provider"] == "anthropic"]
    elif args.models == "openai":
        all_models = [m for m in all_models if m["provider"] == "openai"]

    synthesis_model = next(
        (m for m in all_models if m["alias"] == synthesis_alias),
        all_models[0],  # fallback to first available
    )

    if len(all_models) < 2:
        print(
            "Error: At least 2 models are required for adversarial review.",
            file=sys.stderr,
        )
        sys.exit(1)

    print(f"Running adversarial review with {len(all_models)} models...")
    print(f"  Models: {', '.join(m['alias'] for m in all_models)}")
    print(f"  Synthesis model: {synthesis_model['alias']}")
    print()

    # Phase 1
    reviews = await run_phase1(all_models, content, args.context)
    if len(reviews) < 2:
        print(
            "Error: Fewer than 2 models succeeded in phase 1. Cannot continue.",
            file=sys.stderr,
        )
        sys.exit(1)

    print()

    # Phase 2
    cross_reviews = await run_phase2(all_models, reviews, content, args.context)

    print()

    # Phase 3
    synthesis = await run_phase3(synthesis_model, reviews, cross_reviews, content, args.context)

    # Write output
    output = {
        "content_summary": content[:200] + ("..." if len(content) > 200 else ""),
        "context": args.context,
        "models_used": [m["alias"] for m in all_models if m["alias"] in reviews],
        "was_truncated": was_truncated,
        "reviews": reviews,
        "cross_reviews": cross_reviews,
        "synthesis": synthesis,
    }

    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    with output_path.open("w", encoding="utf-8") as f:
        json.dump(output, f, indent=2, ensure_ascii=False)

    print()
    print(f"Output written to: {output_path}")
    print()
    print("=" * 60)
    print(synthesis)
    print("=" * 60)


if __name__ == "__main__":
    asyncio.run(main())
