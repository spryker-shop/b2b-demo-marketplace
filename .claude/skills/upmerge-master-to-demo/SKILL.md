---
name: upmerge-master-to-demo
description: Run the recurring "upmerge master → master-demo" workflow for the b2b-demo-marketplace repo. Use this skill whenever the user mentions an upmerge task, pastes a JIRA upmerge ticket URL (e.g. CC-XXXXX on board 2237), says "upmerge", "merge master to master-demo", "do the weekly upmerge", "update master-demo from master", or references the recurring task of bringing master changes into master-demo. Runs fully autonomously end to end. A JIRA ticket is OPTIONAL — read it only if the user provides one; otherwise proceed ticketless and name the branch/PR by date. It never moves the JIRA ticket's status.
allowed-tools: Bash, Read, Edit, Write, Skill, ScheduleWakeup, AskUserQuestion, mcp__mcp-atlassian__*, mcp__chrome-devtools__*
user-invocable: true
---

# Upmerge master → master-demo

Bring `master` into `master-demo` through a feature branch, open a PR, and shepherd it through CI. This skill is designed to **run fully autonomously**: make the safe default decision at each step and keep going. Only the hard blockers listed below stop the run.

Detail lives in reference files, loaded when you reach each phase:
- `@.claude/skills/upmerge-master-to-demo/references/merge-and-composer.md` — sync, branch, merge conflicts, composer.lock (Steps 2–5)
- `@.claude/skills/upmerge-master-to-demo/references/demo-reconciliation.md` — Pyz/Demo overrides, deploy, config↔wiring audits (Step 6a–6e, 6g)
- `@.claude/skills/upmerge-master-to-demo/references/cypress-tests.md` — external cypress-tests repo upmerge (Step 6f)
- `@.claude/skills/upmerge-master-to-demo/references/smoke-and-ci.md` — smoke test, `cy:demo`, CI polling (Steps 7–12)

## Autonomy contract

Run unattended. Decide and proceed using these defaults:
- **Merge conflicts** → resolve by the per-type rules in `@.claude/skills/upmerge-master-to-demo/references/merge-and-composer.md` (demo-owned files keep the master-demo side; core-tracking files keep the master side; additive lists keep both).
- **Silent divergences** (Step 6 audits, and the Step 7a visual FE scan) → default to **restoring** the demo-only block/wiring/override to its working master-demo version; the smoke test, the FE scan, and `cy:demo` are the safety net. A visibly-broken redesigned page whose fix is confined to restoring a demo-owned Yves Twig/SCSS override to its master-demo pair is a simple auto-fix (Step 7a); anything needing real template/logic changes escalates.
- **CI failures** → auto-fix the deterministic classes (phpcs, transfer:generate, propel:install, lock sync); push and re-poll.
- Record every non-obvious decision in the PR body so the reviewer can check it.

**Hard stops** — the only situations where you pause for the user:
1. The working tree is dirty at Step 2 (never stash silently).
2. The protected-branch push in Step 6f-5 (`git push origin master-demo` in `tests/cypress-tests`) is denied and the pre-authorized wrapper is unavailable.
3. A CI failure outside the deterministic auto-fix set (phpstan, broken tests, unexpected runtime errors).
4. A local base branch has diverged from origin so a fast-forward is impossible (possible unpushed work).

Everything else: decide and continue.

## Progress tracking — ALWAYS use the task panel

**Mandatory, every run.** Because the run is unattended, the user tracks it through the live task panel. This is not optional and not conditional on run length — do it even for a small or resumed upmerge.

At the very start of the flow (before Step 2), create the 12 steps as a task list with **`TaskCreate`** — one task per numbered step, using these subjects:

1. Sync master & master-demo · 2. Create feature branch · 3. Merge master, resolve conflicts · 4. Refresh composer.lock & install · 5. Reconcile overrides + deploy/config/wiring audits (6a–6e,6g) · 6. Upmerge cypress-tests repo (6f) · 7. Smoke-test + FE-anomaly scan (7a) + run cy:demo (7b) · 8. Push & open PR · 9. JIRA optional comment (never move status) · 10. Schedule CI poll · 11. Poll pipeline & auto-fix · 12. Final report

(Step 0 is folded into task 1; Step 1 ticket-handling happens before task creation since it decides the branch name.) Then drive the panel as you work:
- **`TaskUpdate` → `in_progress`** the instant you begin a step (before its first command).
- **`TaskUpdate` → `completed`** the instant it finishes; move straight to marking the next one `in_progress`.
- Keep exactly one task `in_progress` at a time. On a **hard stop**, leave the blocking task `in_progress` (not completed) so the panel shows where the run halted.
- For the polling-only invocation (`poll PR …`), if no panel exists yet, create just the tail tasks (11–12) and drive those.

## Progress logging — announce every step

The task panel is the primary tracker; the log lines below add the per-step **outcome/decision** detail the panel can't hold. Emit one **START** line as you begin each numbered step and one **DONE** line as you finish it, in plain assistant text (not a tool call), using this exact prefix so the flow is greppable:

```
[upmerge 3/12] START  Create feature branch
[upmerge 3/12] DONE   feature/upmerge-2026-07-01/master-to-master-demo cut from master-demo
[upmerge 6e/12] DONE  config_default.php audit — restored AmazonQuicksight block (1 key, 1 import)
```

Rules:
- Format: `[upmerge <step>/12] <START|DONE> <short description>`. Use the sub-step id where one applies (`6a`, `6e`, `6f-3`, `6g`).
- The **DONE** line states the outcome/decision, not just "done" — branch name, conflict resolutions taken, audit result (restored / none dropped), files aligned, `cy:demo` pass/fail, PR URL. This is the audit trail of an autonomous run.
- Log every autonomous **decision** at the point you make it (which conflict side you kept, which demo block you restored) so the user can follow the reasoning without reading the diff.
- Log each **hard stop** with a `BLOCKED` line: `[upmerge 6f-5/12] BLOCKED  cypress push denied — needs user authorization`, then stop.
- Keep lines short and skimmable; put detail in the PR body, not the log.
- The reference files note where each step's DONE line should report its specific outcome.

## Flow

0. Update local `master` and `master-demo` from origin (same as Step 2, done first so the branch is cut from the true latest).
1. **Ticket (optional).** If the user provided a ticket, use its key for the branch/PR and optionally read it for context. If not, proceed ticketless — don't search, don't ask, don't invent one; name the branch/PR by date. `<TICKET-or-date>` below means the ticket key if provided, else today's date.
2. Sync `master` and `master-demo` → `@.claude/skills/upmerge-master-to-demo/references/merge-and-composer.md`
3. Create the feature branch (ticket-named or `feature/upmerge-YYYY-MM-DD/master-to-master-demo`) → same reference
4. Merge `master` in, resolve conflicts → same reference
5. Refresh `composer.lock`, `composer install` → same reference
6. Reconcile with the incoming changes — all of the following, even on a clean merge:
   - Pyz/Demo overrides incl. Twig shadowing changed core; `deploy.spryker-icpplus.yml` sibling audit; `config_default.php` demo-only block audit (6e); Dependency Provider wiring audit (6g) → `@.claude/skills/upmerge-master-to-demo/references/demo-reconciliation.md`
   - External `spryker/cypress-tests` `master-demo` upmerge to the demo-shop's pinned cypress hash (6f) → `@.claude/skills/upmerge-master-to-demo/references/cypress-tests.md`
7. Smoke-test Yves + Backoffice login (incl. the analytics-gui canary); then visually scan the pages the merge's Twig/SCSS touched for FE anomalies and auto-fix the simple ones (Step 7a); then run the demo Cypress group `cy:demo` (Step 7b) → `@.claude/skills/upmerge-master-to-demo/references/smoke-and-ci.md`
8. Push and open the PR targeting `master-demo` (Step 8 below)
9. JIRA — optional PR-link comment only, and only if a ticket was provided; **never** move the status (Step 9 below)
10. Schedule the CI poll → `@.claude/skills/upmerge-master-to-demo/references/smoke-and-ci.md`
11. Poll and auto-fix pipeline failures → same reference
12. Final report → same reference

## Step 8 — Push and open the PR

Push the branch from Step 3:

```bash
git push -u origin <branch-from-step-3>
```

Create the PR. Title/body adapt to the ticket:
- **Ticket provided:** title `<TICKET>: Upmerge master to master-demo`; include a `JIRA: <TICKET-URL>` line in the body.
- **No ticket:** title `Upmerge master to master-demo (YYYY-MM-DD)`; omit the `JIRA:` line.

```bash
gh pr create \
  --base master-demo \
  --title "<TITLE>" \
  --body "$(cat <<'EOF'
## Summary
Routine upmerge bringing the latest `master` changes into `master-demo`.

## Test plan
- [x] Pyz/Demo override reconciliation (incl. Twig shadowing changed core)
- [x] deploy.spryker-icpplus.yml audited vs sibling deploy files (Step 6d)
- [x] config_default.php audited for dropped demo-only config blocks (Step 6e)
- [x] Dependency Providers audited for dropped demo-only plugin/console registrations (Step 6g)
- [x] spryker/cypress-tests master-demo upmerged to the demo-shop's pinned cypress hash; demo-shop re-pinned (Step 6f)
- [x] Local smoke: Yves login + customer overview; Backoffice login + dashboard + analytics-gui (QuickSight canary)
- [x] Demo Cypress group green locally (npm run cy:demo, ENV_REPOSITORY_ID=b2b-mp)
- [ ] CI pipeline green (incl. the Run Tests (Demo) step)

## Reconciliation notes
<!-- Non-obvious merge resolutions; Pyz/Demo files kept/adapted/aligned; deploy audit result; config_default.php demo-only keys restored or "none dropped"; Dependency Provider registrations restored (where — prefer src/Demo) or "none dropped". -->

## cypress-tests upmerge (Step 6f)
<!-- TARGET_CYPRESS_HASH merged; new cypress master-demo tip pushed (or "push pending — user authorization required"); demo-shop re-pinned; no newer-than-target cypress-master commits leaked. -->
EOF
)"
```

For a ticketed PR, insert the `JIRA: <TICKET-URL>` line under `## Summary`. Capture the PR URL from `gh pr create` for the final report (and the optional JIRA comment).

## Step 9 — JIRA (optional; never move status)

- **No ticket** → skip entirely.
- **Ticket provided** → do not change the ticket's status (leave it for the user to move in the JIRA UI). The only permitted action is a best-effort PR-link comment, and only if the user hasn't asked to stay off JIRA:
  ```
  mcp__mcp-atlassian__jira_add_comment with issue_key=<TICKET> comment="PR opened: <PR-URL>"
  ```
  If it fails, mention it and move on — don't retry or block. Never attempt a status transition.

## Pitfalls that aren't obvious from the commands

- **A clean merge is not a safe merge.** `git merge` flags only overlapping line edits. Four silent losses have no conflict marker: a changed core Twig template behind a Demo/Pyz override (stale render); a dropped demo-only `config_default.php` block whose reader stays wired (runtime crash, Step 6e); a demo-only plugin/console registration dropped from a provider list (feature silently dead, Step 6g); and a **master redesign of a component whose Twig/SCSS the demo overrides** — the merge takes master's incompatible redesign, no marker fires, static analysis and `cy:demo` stay green, but the page renders visibly wrong (Step 7a — the search-form icon-stacking case). The first three are caught by the Step 6 diff-based audits; the fourth needs the Step 7a **visual** scan because a diff can look innocuous while the rendered layout breaks. The config-drop and wiring-drop are two halves of one invariant — for each demo feature, config and wiring are both present or both absent.
- **Demo-only wiring belongs in `src/Demo`, not `src/Pyz`.** `Demo` shadows `Pyz` and `Pyz` is on master's edit surface, so a demo entry in a `Pyz` provider is one master-side edit from silent deletion. When restoring one, relocate it to a `src/Demo` provider extending the `Pyz` one.
- **Cypress: merge the pinned HASH, never cypress master's tip** (Step 6f). Cypress master runs ahead of the shop's pin; merging its tip makes the demo cypress branch assert behavior the shop lacks → false failures.
- **The cypress quality gate false-fails on a feature branch** unless you pass `TARGET_HASH=` (before re-pin) or `DEMOSHOP_REF=HEAD` (after). The plain no-env form is authoritative only in CI / on merged `master-demo`.

## Polling-only invocation

Invoked as `/upmerge-master-to-demo poll PR <N>` (optionally ` ticket <KEY>`), from a `ScheduleWakeup` callback or directly: skip Steps 1–10 and go straight to Step 11 (`@.claude/skills/upmerge-master-to-demo/references/smoke-and-ci.md`). The ticket key is optional — without it, poll and report with no JIRA action.
