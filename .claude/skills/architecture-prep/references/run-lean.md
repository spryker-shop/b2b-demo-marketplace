# Run-lean discipline — directory, State Object, logging, decisions

Read this once at the start of every run, before Step 0. It mirrors the bugfix/prd-development
conventions so the run is self-contained, survives compaction and long autonomous runs, and leaves a
truthful trail. The principle: **teammates do the heavy work and write bulk output to files; the
orchestrator retains only compact state.**

## The run directory (`$ARCH_DIR`)

Every file this skill produces that is **not** part of the deliverable — logs, the interview capture,
research findings, the decision log — lives together in one per-run folder, anchored to the project
root so it's stable regardless of the current working directory:

```
${CLAUDE_PROJECT_DIR:-$(pwd)}/.claude/.cache/architecture-prep/<run-id>/
```

`<run-id>` is a short slug: a JIRA key if the work is ticketed, else a date + brief name (e.g.
`2026-07-23-marketplace-arch`). Note the project's rule that temporary/draft `.md` go under
`.claude/local-trash/` — the cache folder above is that spirit; keep **all** scratch here and never
scatter run files into `architecture/`. Set it once at Step 0:

```bash
ARCH_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}/.claude/.cache/architecture-prep/<run-id>"
mkdir -p "$ARCH_DIR"
ARCH_LOG="$ARCH_DIR/run.log"
```

Files that live here: `run.log`, `decisions.md`, `intake.md`, `research-docs.md`, `research-web.md`,
`research-current-state.md`, and any per-section notes. The **deliverable** (the filled arc42 files
and diagrams) is written into the output target — by default the git-tracked `architecture/` folder —
never into `$ARCH_DIR`.

**Multi-deliverable runs** (see [multi-deliverable.md](multi-deliverable.md)) keep ONE run directory
for the whole batch: `intake-<slug>.md` per project, a single shared `research-docs.md` for the
union of Spryker features, and per-slug research files only for genuinely project-unique topics.
Each deliverable's output target is its own git worktree, recorded in the State Object per slug.

**Writing under `.claude/` paths:** when a run leaves notes/tracking files anywhere under `.claude/`
(this cache folder is fine; skill-improvement notes especially), prefer plain ASCII — `->` not a
typographic arrow, "section 3" not the section sign. The write classifier is stricter there and a
denial costs a round-trip. Deliverable files under `architecture/` are unaffected.

## The step log (`run.log`)

Append-only timeline — milestones, not noise. One timestamped line per meaningful boundary. Get the
timestamp from `date '+%Y-%m-%d %H:%M'` and append with a single redirect so a line is never
half-written:

```bash
echo "[$(date '+%Y-%m-%d %H:%M')] SECTION 03 | written (2 todos)" >> "$ARCH_LOG"
```

Line shapes:
- **Phase boundary:** `[ts] STEP <n> — <name> | START` / `| END <one-line outcome>`
- **Milestone:** `[ts] <PHASE> | <event> | <result>` — `PHASE` is a short tag (`SETUP`, `INTERVIEW`,
  `RESEARCH`, `PLAN`, `SECTION <nn>`, `DIAGRAM`, `CROSSLINK`, `REVIEW`, `HANDOFF`); `result` is
  `OK`/`DONE`/`SKIP`/`FAIL` with a short parenthetical.

Log at least: the Step 0 config (one line — mode, stage, chosen sections, output target, grounding),
interview complete, each research source done/skipped, each section written with its TODO count, the
cross-link pass with the total TODO count, the review outcome, and the handoff. A failed/blocked step
is logged `FAIL`/`SKIP` with a one-line reason *before* you stop, so the log always ends with the cause.

## The State Object (the only thing the orchestrator retains)

Keep this compact block current in your head and mirror it into the log at each boundary. Everything
bulky lives in a file you re-open on demand.

- `mode` (Gated / Autonomous), `stage` (greenfield / existing), `output_target` (default
  `architecture/`), `grounding` (permitted? y/n).
- **Selected sections** — the exact arc42 files chosen this run; you touch only these.
- **Extra expectations** — any Step 0 delta from the standard flow (e.g. "also produce ADR-001",
  "single store only", "explore ERP integration as an SD"), so every later step honors it.
- **Interview status** — done? path to `intake.md`. One-line note of any big gaps.
- **Research summaries** — for each source (docs / web / current-state): a 1–3 line takeaway + path
  to the findings file. Not the findings themselves.
- **Per-section verdict** — for each selected section: `written | pending | skipped`, its TODO count,
  and the file path. For a section with open TODOs, keep only the ≤5 TODO items, not the prose.
- **Consolidated TODO list** — every unresolved gap across all sections (built at Step 5), for the
  report.
- Pointers to `decisions.md` and `run.log`.

If you need detail you dropped (a full research finding, a section's prose), `Read` the file — pull
the lines you need, don't reload everything.

## The plan task list

Alongside the State Object, keep a plan task list (created at Step 3) with one task per selected
section plus tasks for diagrams, the cross-link pass, and review. It's the run's live at-a-glance
position: exactly one task `in_progress`, completed behind, pending ahead. It survives compaction and
is the cheapest thing to read to re-orient a resumed run. Never mark a section task complete whose file
wasn't actually written with real content.

Three surfaces, three roles — keep them distinct:
- **`run.log`** — the append-only *timeline* (what happened, when).
- **`decisions.md`** — the *rationale* (why each fork was resolved a given way).
- **Task list** — the *current state* (where the run is now).

## The decision & question log (`decisions.md`)

From Step 0 onward, keep an append-only log with two sections:

- **CRITICAL DECISIONS** — every fork you resolved on your own (mostly in Autonomous mode): the
  choice, the alternatives rejected, and the one-line reason. E.g. "Assumed native Spryker auth (no
  CIAM) for section 3 — interview didn't name an IdP and no auth doc was provided; flagged as a TODO
  for the user to confirm."
- **OPEN QUESTIONS / RISKS / GAPS** — anything you couldn't fully resolve and proceeded past: unknown
  volumes, unconfirmed integrations, sections left thin. These become the consolidated TODO list and
  feed the Step 6 report.

Update it at each section-write and whenever you make a non-obvious content choice — not just at the
end. In Gated mode most forks become questions to the user instead; still log the resolution.

## Why lean matters here

An architecture run fans out to many section-writer teammates and several research teammates. If the
orchestrator tried to hold every research finding and every section's full prose, it would bloat fast
and the final cross-link pass — the one step that legitimately needs to see everything — would have no
room. Keep the orchestrator to the State Object; let the files carry the weight.
