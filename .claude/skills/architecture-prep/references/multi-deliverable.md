# Multi-deliverable runs — N inputs → N architectures → N PRs

A batch run is a natural use case: an agency/PS team turning several prospect TADs into
architecture docs at once, or one account with staged variants. The trap is structural: **one repo
has exactly one `architecture/` folder**, so N deliverables written naively into the working tree
collide and overwrite each other — a naive run loses N-1 of the N documents. This file is the
recipe that avoids reinventing (or botching) the fan-out. Read it at Step 0 whenever the intake
contains more than one distinct project input (N TADs / tickets / briefs) and the user expects
separate deliverables or PRs.

## One worktree + branch per deliverable

Give each deliverable its own git worktree and branch so each fills its *own* `architecture/`
folder and becomes its own PR:

```bash
# from the repo root, per deliverable slug, branching from the agreed base:
git worktree add ../arch-wt-<slug> -b arch/<date>-<slug> <base-branch>
```

- **Base branch:** all deliverable branches fork from the same explicit base — the branch the user
  wants the PRs against (the current branch unless they said otherwise). Pass it explicitly; an
  implicit `-b` branches from whatever HEAD happens to be, which drifts if you create worktrees at
  different moments.
- **Scaffold before you fan out:** if `architecture/` is missing (Step 0.1), scaffold and commit it
  **on the base branch first**, so every worktree inherits one identical template instead of N
  divergent scaffolds.
- **Naming:** branch `arch/<date>-<slug>`, worktree dir `../arch-wt-<slug>` (a sibling of the repo,
  never inside it). Consistent names make the Step 7 command block trivial to emit.
- Each writer teammate gets its worktree path as the output target and must never write outside it.
- **Section selection is per deliverable.** Default to one section set for the whole batch (state
  that as the assumption), but honor any per-project differences the inputs or the user's request
  imply; record the chosen set per slug in the State Object.
- **Cleanup is part of the run:** after the PRs are open (or handed off), `git worktree remove
  ../arch-wt-<slug>` for each. Log it. Leaving stale worktrees behind is a mess the next run trips
  over.

## Isolate at the deliverable boundary, not the section boundary

For a single-deliverable run the default fan-out is one teammate per arc42 section. For N
deliverables, **invert the altitude: one isolated writer teammate per deliverable**, each writing
all of its project's sections in its own worktree. Reasons this is the right granularity:

- **No fact bleed.** Project A's CIAM/ERP specifics must never surface in project B's document.
  Separate contexts guarantee isolation structurally; one context "being careful" does not.
- **Context economy.** A full arc42 fill is large; N of them in one context blows the orchestrator.
  One teammate per deliverable keeps every context lean and the orchestrator at State-Object size.
- **Parallelism and thoroughness.** N independent teammates run concurrently, and a teammate
  anchored only on ITS input produces more complete, less cross-anchored work.
- **No file races.** Per-section teammates across N worktrees would mean N×12 agents coordinating —
  heavy for no isolation benefit, since the sections within one coherent TAD don't need independent
  contexts. The projects do.

Spawn deliverable writers as **fresh `general-purpose` agents, never forks** — a fork inherits the
orchestrator's context, including the other projects' intakes, which reintroduces exactly the fact
bleed the worktree split exists to prevent.

Each deliverable teammate also runs its **own Step 5 cross-link pass** as its final act — it already
holds all its sections, whereas the orchestrator doing N full cross-link passes would defeat the
context economy. That includes **owning its § 12 glossary**: in a batch run the deliverable teammate
(not the orchestrator) writes/updates the glossary to cover exactly the terms its sections
introduced. The orchestrator then spot-checks each deliverable cheaply (READMEs list the SDs/ADRs,
"See diagram" links resolve, TODO counts match the teammate's report) rather than re-reading every
section.

**Gated mode gates per deliverable:** the "write in batches, pause to confirm" rhythm from Step 4
maps to deliverables here — present each completed deliverable (or a small batch of them) for the
user's confirmation before opening its PR, not per section inside a deliverable.

## Research once, shared across deliverables

Sibling Spryker projects overlap heavily on the features they name (Dynamic Multi-Store, Merchant
Relationship, Persistence ACL, Data Import + P&S optimization, Glue APIs, OAuth SSO, ACP payments…).
Researching per project multiplies the cost for near-identical answers.

- Compute the **union** of Spryker features/modules named across all intakes and run the docs
  research teammate **once**, writing one shared `research-docs.md`. Note per feature which
  project(s) need it. All writer teammates read the same file and cite the same URLs.
- Only run extra, project-scoped research for something unique to one project (e.g. one project's
  exotic PSP), into `research-web-<slug>.md`.
- Run-directory layout for N deliverables: `intake-<slug>.md` per project, one shared
  `research-docs.md`, per-slug extras only where genuinely unique.

**Fetching docs.spryker.com:** the site is JS-rendered, so `WebFetch` on docs pages returns only a
nav shell. Pull the page's markdown source from the public `spryker/spryker-docs` GitHub repo
instead (raw file or Contents API) — it saves a whole class of failed fetch round-trips.

## Step 7 for N PRs — prepare push-ready, hand the push to the user

Pushing and opening PRs is an outward-facing gate: the environment may not allow `git push`, and an
in-conversation "yes, push" does not satisfy a harness permission rule. Don't discover this after
half the pushes; structure the handoff so a block costs nothing:

1. **Commit everything in every worktree first**, so all N deliverables are push-ready before any
   push is attempted. Write a PR body per deliverable to the run directory
   (`$ARCH_DIR/pr-body-<slug>.md` — the summary, section list, and open-TODO list from that
   deliverable's report); the command block below references these files.
2. Attempt the push/PR only if the user explicitly asked for PRs this run. If a push is denied,
   don't retry or work around it — emit the exact, copy-paste-ready command block for the user to
   run themselves (with the `!` prefix in this harness), one stanza per deliverable:

   ```bash
   git -C ../arch-wt-<slug> push -u origin arch/<date>-<slug>
   gh pr create --repo <org>/<repo> --head arch/<date>-<slug> \
     --title "Architecture: <Project>" --body-file $ARCH_DIR/pr-body-<slug>.md
   ```
3. Only after the PRs exist (or the user takes over): worktree cleanup as above.

One more operational note: when writing notes under `.claude/` paths (skill improvements, tracking
files), prefer plain ASCII (`->` not a typographic arrow, "section 3" not the section sign) — the
write classifier is stricter there, and a denial costs a round-trip. Deliverable files under
`architecture/` are unaffected.
