---
name: architecture-prep
description: >
  Establish or fill in a Spryker project's version-controlled architecture record — the
  `architecture/` folder as arc42 sections + C4/sequence diagrams, ADRs, and Solution Designs.
  Interviews the user, researches Spryker docs/web/the running system, and writes each section to
  decision-grade depth (release-phased scope, components/connectors tables, per-phase volume planning,
  multi-store strategy). Runs in ANY Spryker project/demoshop and scaffolds the
  mandatory `architecture/` folder if missing — for greenfield, an existing build (onboarding), or
  turning a business brief into an architecture doc that feeds a PRD. Accepts a Spryker TAD (Target
  Architecture Definition Confluence page) or similar structured brief as the complete intake — no
  interview — and handles batch runs: N TADs/briefs → N architecture docs → N PRs, one git worktree
  per deliverable. Trigger on "prepare/fill in the architecture", "set up the architecture folder",
  "do the arc42/C4 docs", "document the system context / building blocks / runtime flows", "capture
  this as an ADR/solution design", or "turn this TAD / these TADs into architecture docs (as PRs)" —
  even without saying "arc42" or "C4". Do NOT use to write a PRD (product-requirement-document),
  build from a PRD (prd-development-local), or fix a bug (spryker-bugfix).
---

# Architecture Preparation Workflow (interview → filled arc42 docs)

## What this is

This skill is the **conductor** that turns a mostly-empty `architecture/` template folder into a
real, project-specific architecture document. It does **not** invent a new format — Spryker projects
ship an arc42 + C4 template under `architecture/` (see `architecture/README.md` and
`architecture/PUBLIC-DOC-GUIDELINE.md`). Your job is to **gather every useful input — a thorough
interview, or the structured brief/TAD that replaces it — research what's true, and fill those
templates in** — replacing the example content with the project's reality — so the result is ready
to feed a PRD and the development that follows.

**`architecture/` is a mandatory part of every Spryker project.** This skill runs **inside any
Spryker project or demoshop** — b2b / b2c / marketplace / suite — from that project's root. The
`architecture/` folder is expected to exist there; treating it as a first-class, version-controlled
project artifact (alongside `src/`, `config/`, `data/`) is the point. If a project or demoshop
doesn't have it yet, the skill **scaffolds it** (Step 0) rather than refusing — because a Spryker
project without an architecture folder is a gap to fix, not a blocker.

The value is in the **control flow**: collect a lot of information up front, ground it in real
Spryker documentation and (for existing projects) the real codebase/runtime, then fan the writing
out to teammate subagents so the orchestrator stays lean and the sections stay consistent and
cross-linked.

**The architecture folder is the deliverable, edited in place.** By default this skill writes into
the git-tracked `architecture/` folder, replacing template/example content with project content.
Run scratch (logs, research findings, the decision log) lives separately under a cache folder — it
never pollutes the deliverable. Confirm the target at Step 0.

## This is an orchestrator — delegate, don't do it all inline

Two reasons to push heavy work into subagents (the "teammates" the user asked for): the main context
stays small and survives long runs, and independent teammates produce more thorough, less anchored
work than one context writing everything sequentially.

- **Research** runs in parallel teammates (docs / web / current-state), each writing findings to a
  file and returning a compact summary — never raw dumps.
- **Section writing** runs as **one teammate per selected arc42 section**, in parallel, each fed the
  interview answers + shared research and told exactly which file(s) and diagram(s) to produce.
- The **orchestrator** holds only the compact State Object, then does the final cross-link + consistency
  pass itself (it's the only step that needs to see all sections at once, and it's cheap).
- **Multiple deliverables invert the granularity.** When a run has several distinct project inputs
  (e.g. several TADs, each expecting its own doc/PR), give each deliverable **one isolated writer
  teammate with its own intake file and its own git worktree** — never let one context write two
  deliverables (fact bleed between projects, blown context) and don't fan out per-section across
  projects (N×12 agents for no isolation gain). Isolate at the deliverable boundary, not always the
  section boundary. Read [references/multi-deliverable.md](references/multi-deliverable.md) at Step 0
  whenever N > 1.

Spawn teammates with the `Agent` tool. Prefer `subagent_type: "Explore"` for read-only research/
current-state investigation and `subagent_type: "general-purpose"` (or a `fork`) for section writers
that must create files. **Exception: multi-deliverable writers must be fresh `general-purpose`
agents, never forks** — a fork inherits the orchestrator's full context (including the other
projects' intakes), which defeats the isolation that prevents fact bleed. Keep every teammate on a
Claude model.

## The companion files (read the right one at the right time)

This SKILL.md is the **spine**. The detail lives in the references — read the relevant one before
the step that needs it, not all up front:

- [references/run-lean.md](references/run-lean.md) — the run directory, the **State Object**, the
  **logging** discipline (bugfix-style `run.log`), the **decision log**, and the plan task list.
  **Read once at the start of every run, before Step 0.**
- [references/questionnaire.md](references/questionnaire.md) — the **canonical fillable question list**
  (groups A–H + run-config R; each question tagged `[REQUIRED]`/`[optional]` and mapped to an arc42
  section). Users can pre-fill it to skip the interview, or partly fill it to shorten it. **This is the
  single source of what to ask.** Read alongside interview.md before Step 1.
- [references/interview.md](references/interview.md) — **how** to collect the questionnaire: detect a
  pre-filled/partial questionnaire (ask only the blanks), offer "fill the list yourself vs interview
  me", group the questions into `AskUserQuestion` batches, ask for existing docs first, and **always
  write every answer to `intake.md`**. **Read before Step 1 — unless a TAD/structured brief was
  provided (then tad-mapping.md replaces it).**
- [references/tad-mapping.md](references/tad-mapping.md) — the **TAD fast-path**: when a Spryker TAD
  (or similar structured brief) is the input, it IS the intake — the TAD-section → arc42-section
  mapping, diagram harvesting, and what TADs typically leave open. **Read at Step 0 whenever the
  intake includes a structured architecture doc.**
- [references/multi-deliverable.md](references/multi-deliverable.md) — batch runs: N inputs → N
  worktrees/branches → N PRs, one isolated writer per deliverable, research shared once, push-ready
  handoff. **Read at Step 0 whenever there is more than one deliverable.**
- [references/sections.md](references/sections.md) — per-arc42-section writer guidance (what goes in
  each of the 12 files, which diagrams pair with which section, the diagram color/style convention,
  and the ADR / Solution Design sub-workflow). **Each section-writer teammate reads the § for its
  own section; the orchestrator skims the map.**
- [references/architecture-depth.md](references/architecture-depth.md) — the **content-depth bar**:
  where each kind of depth lands in arc42, the two rationale tables (Components + Connectors) that
  must accompany the architecture diagram, the extended volume checklist, release phasing, and the
  rule that risk mitigations cite real Spryker features. **Read by the orchestrator once, and by every
  section writer for its part — this is what makes the output a document an architect would sign off,
  not a thin template fill.**
- [references/review.md](references/review.md) — the **Spryker-specific self-review checklist**: the
  Step 6 quality gate run by an independent reviewer subagent (grounding & no fabrication, depth-bar
  met, the build-ready five, arc42 structure & cross-consistency), returning a per-section verdict +
  ranked defect list. **Read by the orchestrator before Step 6, and by the reviewer subagent as its
  brief.**
- [references/preview.md](references/preview.md) — the optional **localhost HTML preview + PDF export**:
  a self-contained Markdown+Mermaid renderer served on localhost that doubles as the print-to-PDF path
  (one printable document → Save as PDF). Throwaway scratch, never committed. **Read at Step 7 when the
  user wants to view or export the finished document.**

## Operating principles

- **Aim for decision-grade depth, expressed as code — then make it build-ready.** The bar (see
  [references/architecture-depth.md](references/architecture-depth.md)) is release-phased scope, a
  Components + Connectors rationale pair beside every architecture diagram, a rich per-phase volume
  table, multi-store strategy, and risk mitigations that cite real Spryker features — all delivered as
  version-controlled arc42 + Mermaid (`.mmd`) diagrams. Beyond that, the review-proven build-ready additions:
  t-shirt sizing per custom module + first-pass infra sizing, SD data models drafted (never a bare
  TODO), launch-blocking unknowns promoted to decision gates with owners/dates, and load tests
  anchored by explicit interim assumptions. Thin template-filling is the thing to avoid; "sequences
  but doesn't size" is the second thing to avoid.
- **Collect first, write second.** The single biggest failure mode is writing thin, generic sections
  because the interview was shallow. Front-load information gathering — ask for real documents, then a
  broad batched interview, then research — and only start writing once you have substance. A section
  you can't ground in a real answer, a doc, a doc-research finding, or the codebase should be flagged
  as a gap, not filled with plausible-sounding filler.
- **Ground every claim.** Spryker behavior goes through `spryker-docs-research`, never from memory.
  Claims about *this* project's current system (for existing projects) go through the codebase /
  `spryker-runtime`, never assumed. External/industry facts go through web research. If a fact has no
  source, say so in the doc rather than asserting it.
- **Respect the template, replace the examples.** The template's example content (SAP, Akeneo, Auth0,
  the volume table, the diagram color scheme) is illustrative. Keep the *structure* and the *style*;
  swap the *content* for the project's reality. Never leave a section half template / half real without
  marking what's still a placeholder.
- **Only the sections the user chose.** Step 0 asks which arc42 sections to produce this run. Fill
  exactly those; leave the others as-is (untouched template). Don't silently expand scope.
- **The deliverable is version-controlled prose + diagrams-as-code.** Markdown + **Mermaid (`.mmd`)
  only** — every diagram type including ERDs (`erDiagram`). No PlantUML/`.puml` (it can't render in the
  browser preview, the PDF, or GitHub) and no binary formats. Diagrams follow the project's universal
  color scheme (in sections.md).
- **Honest status.** If a section is thin because the interview couldn't fill it, mark it (an
  `> **TODO:**` note naming what's missing) rather than dressing it up. A truthful gap is more useful
  to the PRD author than confident invention.
- **Autonomous means autonomous (after Step 0/1).** In Autonomous mode, after the intake interview is
  answered you run to the Step 6 review without further `AskUserQuestion`; every fork becomes a logged
  CRITICAL DECISION (see run-lean.md), not a question. Gated mode keeps its confirmation stops.

---

## The workflow (Steps 0–7)

Read [references/run-lean.md](references/run-lean.md) now, then begin.

### Step 0 — Intake & mode

0. **Classify the intake shape first** — it decides how the rest of Step 0/1 runs:
   - **Structured brief(s) provided** (a Spryker TAD Confluence page or equivalent): read
     [references/tad-mapping.md](references/tad-mapping.md). The document replaces the interview;
     from the opening batch below, ask only what neither the doc nor the user's request already
     states (typically: sections, mode, output target) — and if the request states those too, ask
     nothing at all.
   - **More than one deliverable** (N inputs, each expecting its own doc/PR): read
     [references/multi-deliverable.md](references/multi-deliverable.md) and set up one worktree +
     branch per deliverable before any writing.
   - **No structured doc, single deliverable**: the standard flow below, interview included.
1. **Locate or scaffold the template.** Confirm `architecture/` exists at the current project/demoshop
   root and read its `README.md`. **It's mandatory — if it's missing, scaffold it before continuing**
   (it's a gap, not a stop): copy the full `architecture/` template tree (all 12 section files, the
   `04`/`09` READMEs + `sd-000`/`adr-000` templates, `diagrams/` examples, `README.md`,
   `PUBLIC-DOC-GUIDELINE.md`) from a known Spryker project that has it — e.g. this
   b2b-demo-marketplace, or a sibling demoshop/suite checkout under the same dev root — into the
   current project's root. If no local source is reachable, recreate the structure from the section
   list below and the templates described in `sections.md`. Never invent a different structure — the
   arc42 + C4 layout is the standard. Tell the user you scaffolded it and from where.
2. **Ask the opening batch** (one or more `AskUserQuestion` calls — this is the "multi-tab section in
   the beginning" the user wants). Cover at minimum, in this order of priority:
   - **Existing documents first.** "Can you share any business requirements, briefs, RFPs, discovery
     notes, slide decks, existing diagrams, or tickets? Paste text or give file paths." This is the
     highest-value input — ask for it before anything else and read everything provided.
   - **Project stage:** greenfield / new, or an existing project with code (this repo is existing).
     This decides whether current-state investigation is offered.
   - **Which arc42 sections** to produce this run (multi-select — see the list below). Default-suggest
     the template's recommended path (1, 3, 10, then 5, 2) but let the user pick.
   - **Autonomy mode:** Gated (confirm before writing each section batch) or Autonomous (interview
     once, then write everything, logging decisions).
   - **Current-state grounding** (existing projects only): may I read the codebase / run
     `spryker-runtime` to ground sections about the actual system?
   - **ADRs / Solution Designs:** any decisions already made to capture as ADRs, or areas to explore
     as Solution Designs?
   See [references/interview.md](references/interview.md) for the full question bank and how to group
   it into tabs. Batch aggressively (up to 4 questions per `AskUserQuestion`, several calls) so the
   user is interviewed carefully but in one pass, not drip-fed.
3. **Set up the run.** Create the run directory, the `run.log`, the decision log, and the State Object
   per run-lean.md. Record: template path, output target (default `architecture/` in place), chosen
   sections, mode, project stage, grounding permission. Log the Step 0 config as one line.

The 12 arc42 sections (offer as the multi-select):

| # | File | Focus |
|---|------|-------|
| 01 | `01-introduction-and-goals.md` | Requirements overview, quality goals, stakeholders, migration |
| 02 | `02-constraints.md` | Technical / organizational / convention constraints |
| 03 | `03-system-scope-and-context.md` | Boundaries, external systems, integrations (+ C1) |
| 04 | `04-solution-designs/` | RFC-style exploration docs (on request) |
| 05 | `05-building-block-view.md` | Decomposition, containers, components (+ C2/C3) |
| 06 | `06-runtime-view.md` | Runtime scenarios (+ sequence diagrams) |
| 07 | `07-deployment-view.md` | Infrastructure & topology |
| 08 | `08-crosscutting-concepts.md` | Cross-cutting patterns |
| 09 | `09-architecture-decisions/` | ADRs (on request) |
| 10 | `10-quality-requirements.md` | Volume planning, testing strategy, quality scenarios |
| 11 | `11-risks-and-technical-debt.md` | Risks & debt |
| 12 | `12-glossary.md` | Domain & technical terms |

### Step 1 — Deep interview (batched, up front) — or the TAD fast-path

**If Step 0 classified the intake as a structured brief, skip the interview**: pull the TAD(s) per
[references/tad-mapping.md](references/tad-mapping.md), save each verbatim as the project's
`intake(-<slug>).md`, annotate with the TAD→arc42 mapping, and log `INTERVIEW | SKIP (TAD intake)`.
A TAD is usually richer than a live interview; interviewing on top of it wastes the user's time.

**If the user pre-filled [references/questionnaire.md](references/questionnaire.md)** (fully or
partly), follow interview.md Rule 0b: a fully-filled questionnaire skips the interview
(`INTERVIEW | SKIP (questionnaire pre-filled)`); a partial one means you ask ONLY the still-blank
questions. Either way copy the provided answers verbatim into `intake.md`.

Otherwise run the intake interview per [references/interview.md](references/interview.md), driving the
questions from [references/questionnaire.md](references/questionnaire.md). First (unless fully
autonomous) offer the user the choice to fill the questionnaire themselves vs be interviewed
(interview.md Rule 1b). Then ask everything useful for the **selected** sections in batched
`AskUserQuestion` calls (one call per questionnaire group), read every document the user provided, and
**write every answer — from any source — into a single `intake.md`** under the run directory (keyed by
question ID, with each fact's source noted). This is the shared source every section writer will read.
Do not start writing sections until this file has real substance. Log `INTERVIEW | complete`.

> In **Gated** mode you may run a short second batch of follow-ups after reading the provided docs, if
> the docs surface obvious gaps. In **Autonomous** mode, ask once here; fill remaining gaps from
> research or mark them as TODOs — do not come back with more questions.

### Step 2 — Research (parallel teammates)

Spawn research teammates **in parallel**, each writing to a findings file under the run directory and
returning a compact summary:

- **Spryker docs teammate** — via `Skill(spryker-docs-research)` (or an `Explore` teammate that
  invokes it): confirm how the Spryker features/modules named in the interview actually work, the real
  actor names, PBC/feature names, integration patterns, and Publish & Sync behavior. Everything that
  will appear in sections 3/5/6/8 as "how Spryker does X" comes from here. → `research-docs.md`
  For a **multi-deliverable run, this runs ONCE** over the union of features named across all
  intakes, noting per feature which project(s) need it — never once per project (near-identical
  answers at N× the cost). Tip: docs.spryker.com is JS-rendered — fetch page markdown from the
  public `spryker/spryker-docs` GitHub repo instead of WebFetch-ing the site.
- **Web research teammate** — via `WebSearch`/`WebFetch`: external systems named in the interview
  (the real ERP/PIM/CIAM/PSP products), standards (arc42, C4, GDPR, protocol specs), and any
  industry/volume benchmarks the user referenced. → `research-web.md`
- **Current-state teammate** *(existing projects, only if grounding was permitted)* — via
  `Skill(spryker-runtime)` and reading the codebase: which stores/locales/currencies exist, installed
  modules, deployment files (`deploy.*.yml`), real integrations, actors at `/user`. Grounds sections
  3/5/7 in what's actually there. → `research-current-state.md`

Skip any teammate that has no relevant input (e.g. no external systems → no web teammate). Log each as
`RESEARCH | <source> | done`. Keep only each teammate's summary in the State Object; the detail stays
in the file.

### Step 3 — Plan the section fan-out

Turn the selected sections into a plan task list (one task per section + tasks for diagrams, the
cross-link pass, and review). For each selected section, note in the State Object which inputs it
needs (which interview answers, which research file) and which diagram file(s) it pairs with (see the
map in [references/sections.md](references/sections.md)). This is the brief each writer teammate gets.
For a **multi-deliverable run**, plan one task per deliverable instead (its worktree, its intake
file, its selected sections, the shared research) plus the spot-check and handoff tasks.

### Step 4 — Write sections (parallel teammates)

For a **multi-deliverable run**: spawn **one isolated teammate per deliverable** instead — each
writes all of its project's selected sections in its own worktree, following the same per-section
guidance below, and finishes with its own Step 5 cross-link pass (see
[references/multi-deliverable.md](references/multi-deliverable.md)).

Otherwise spawn **one teammate per selected section**, in parallel. Each teammate:
- reads **its own §** in [references/sections.md](references/sections.md), the shared `intake.md`, and
  the research file(s) relevant to it;
- reads the **current template file** it's replacing (to keep the structure/heading style);
- fills the section with project-specific content, replacing example content, and marks any
  unfillable part with a `> **TODO:** …` note naming what's missing;
- produces the paired **diagram file(s)** as Mermaid `.mmd` (ERDs included — `erDiagram`, never
  `.puml`) following the color/style convention;
- edits the real file(s) under the output target and returns a compact "done + what I wrote + open
  TODOs" summary.

Sections 04 (Solution Designs) and 09 (ADRs), if selected, follow their dedicated sub-workflow in
sections.md (they create numbered `sd-XXX` / `adr-XXX` files from the templates, not the section body).

In **Gated** mode, write in batches and pause for the user to confirm a batch before the next; in
**Autonomous** mode, run all writers, logging any content decision as a CRITICAL DECISION. Log each
section `SECTION <nn> | written (Ntodos)`.

### Step 5 — Cross-link & consistency pass (orchestrator, inline)

This is the one step the orchestrator does itself, because it needs all sections at once and it's
light. (Exception: in a multi-deliverable run each deliverable teammate already ran this pass for
its own document — the orchestrator only spot-checks each: README indexes list the SDs/ADRs, "See
diagram" links resolve, TODO counts match the teammate's report.) Read the written sections and:
- fix cross-references (section 3 → C1, section 5 → C2/C3, section 6 → sequences, the "See diagram"
  links must point at files that exist);
- reconcile terminology against section 12 (every domain/tech/Spryker term used should be defined);
- ensure external systems named in section 3 match those in 2/6/11 and the diagrams;
- verify every `> **TODO:**` is real (nothing was quietly left as template example content);
- optionally render/validate Mermaid syntax by parsing the fenced blocks.
Collect every open TODO into a single list for the report. Log `CROSSLINK | done (Ntodos total)`.

### Step 6 — Self-review **and fix** (review → fix → re-verify loop)

Review here is not a report you hand off — it is a **loop that improves the document until it passes.**
Never stop at "the reviewer found N issues"; the run is done only when those issues are fixed (or
consciously accepted as owned TODOs). The loop:

**6a — Review.** Spawn one **review subagent** to grade the written document against the
Spryker-specific self-review checklist in [references/review.md](references/review.md). Read that file
now. The reviewer:
- must be **independent of the writers** — a fresh `Explore` or `general-purpose` teammate on a Claude
  model, never a fork of the orchestrator and never the same context that wrote the sections (an author
  can't see its own gaps);
- is fed: the list of files written this run, the run's `intake.md`, `research-docs.md`, and
  `references/review.md`;
- returns a **per-section verdict + a ranked defect list** (BLOCKER → MAJOR → MINOR), each finding
  located (`file:heading`) and actionable — it does **not** rewrite sections.

Log `REVIEW | round <n> | <overall verdict> | <b>/<m>/<minor>`.

**6b — Fix.** **Every review round that finds a BLOCKER or MAJOR is followed by a fix pass — this is
mandatory, not optional, in both modes.** Route each finding to a fix:
- Dispatch the fixes to **writer teammates** (per section, same isolation as Step 4 — in a
  multi-deliverable run, each deliverable's own writer in its own worktree), each given the specific
  findings for its file(s) and told to fix in place and re-ground any changed claim via
  `research-docs.md`. Small mechanical fixes (a broken cross-link, a stray template label) the
  orchestrator may apply inline.
- A finding is resolved in exactly one of two honest ways: **fixed**, or **converted to a triaged
  owned gap** (client-owned TODO / decision gate / stated interim assumption per
  [architecture-depth.md](references/architecture-depth.md)) — never silently dropped. "Can't fix from
  the inputs" means *convert to a decision gate or TODO with an owner*, not leave the defect standing.
- Log each fix as a decision: `FIX | round <n> | <file> | <finding> → <fixed|gated|todo>`.

**6c — Re-verify.** After a fix pass, **spawn a fresh reviewer again** (a new independent context —
don't ask the same reviewer to bless its own re-read) scoped to the changed files, confirming the
previous findings are gone and no new BLOCKER/MAJOR was introduced. Loop 6a→6b→6c until the exit
condition, capped at **3 rounds** to stay bounded:
- **Exit:** zero open BLOCKERs and zero open MAJORs (remaining items are all triaged owned gaps or
  MINORs). Then stop.
- **If round 3 still has an open BLOCKER/MAJOR:** stop looping and escalate it explicitly in the final
  summary as an unresolved blocker with why it couldn't be fixed — do not hide it or pretend it passed.

Mode differences are only about *who approves*, not *whether fixing happens*:
- **Gated:** run 6a, show the user the ranked findings, and fix (6b) the ones they approve, then
  re-verify (6c). The user's sign-off is the final gate on top of a clean re-verify.
- **Autonomous:** run the full 6a→6b→6c loop yourself without asking — fix all BLOCKERs and MAJORs,
  triage the rest, and only present once the exit condition is met (or round 3 is exhausted).

For a **multi-deliverable run**, the whole 6a→6b→6c loop runs **per deliverable** in its own
worktree/intake, the same way writers are isolated.

**6d — Present.** Once the loop exits, present the summary: which sections were written, which diagrams
produced, the consolidated (triaged) TODO/gap list, any CRITICAL DECISIONS, the **final reviewer
verdict**, and a short **before → after** note (issues found vs. fixed vs. accepted-as-gap across the
rounds). Show file paths (absolute, clickable, per the project's file-reference format).

The checklist is a quality gate, not a rubber stamp — but architecture prose is ultimately a
human-judgment artifact, so in Gated mode the user's review remains the final sign-off.

### Step 7 — Handoff

State plainly that the architecture folder is filled and ready to feed a **PRD** (point at the
`product-requirement-document` skill) and then development (`prd-development-local`). Do **not** commit
automatically — architecture docs are reviewed via pull request (per the template's own guidance);
suggest the branch/commit and let the user run it, unless they explicitly asked you to commit. List the
remaining TODOs so the user knows exactly what still needs a human answer.

**Offer a browser preview + PDF export.** The finished document is Markdown + Mermaid — hard to read as
raw files. Offer a **localhost HTML preview** (Markdown + diagrams rendered) that doubles as the
**PDF export path**: it's built as one printable document, so `Cmd/Ctrl+P → Save as PDF` gives the whole
architecture as a single file. Read [references/preview.md](references/preview.md) for the
self-contained renderer, the serve command, and the manual + headless-Chrome PDF routes. The preview is
throwaway scratch under the run cache dir — it never touches the committed `architecture/` Markdown. In
Gated mode ask if they want it; in Autonomous mode generate it and hand over the URL + the "print to
PDF" one-liner.

When the user **did** ask for commits/PRs (typical for multi-deliverable runs): treat push/PR as an
outward-facing gate. Commit everything push-ready **first** (all branches/worktrees), then attempt
the push; if the environment denies it, don't retry or work around the denial — an in-conversation
"yes" doesn't satisfy a harness permission rule. Emit the exact `git push` + `gh pr create` command
block for the user to run themselves (with the `!` prefix), then finish the rest of the handoff
(TODO list, worktree cleanup once PRs exist). See
[references/multi-deliverable.md](references/multi-deliverable.md) for the N-PR command-block shape.

---

## Quick stage → tool map

| Stage | Delegate to |
|-------|-------------|
| Ticket / doc intake | filled [questionnaire.md](references/questionnaire.md) → ask only blanks; `AskUserQuestion` batches per [interview.md](references/interview.md) + read provided files; TAD → [tad-mapping.md](references/tad-mapping.md) fast-path, no interview |
| Spryker feature/behavior research | `Skill(spryker-docs-research)` (in an `Explore`/`general-purpose` teammate) — once per run, shared across deliverables |
| External systems / standards research | `WebSearch` / `WebFetch` teammate |
| Current-state grounding (existing project) | `Skill(spryker-runtime)` + codebase read teammate |
| Section writing (single deliverable) | one `general-purpose`/`fork` teammate per section |
| Multi-deliverable writing | one isolated teammate per deliverable, own worktree ([multi-deliverable.md](references/multi-deliverable.md)) |
| Cross-link & consistency | orchestrator, inline (multi-deliverable: each writer, orchestrator spot-checks) |
| Self-review | independent reviewer teammate against [review.md](references/review.md) → ranked findings; orchestrator applies fixes (Autonomous) or surfaces to user (Gated) |
| Preview / PDF export (offered at handoff) | self-contained localhost renderer per [preview.md](references/preview.md) → browser view + `Cmd/Ctrl+P` Save-as-PDF (or headless-Chrome `--print-to-pdf`) |
| Push / PRs (only if asked) | commit push-ready first; on denial emit the `!`-prefixed command block for the user |
