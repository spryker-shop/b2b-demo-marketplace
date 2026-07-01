# Cypress-tests repo upmerge (Step 6f)

Detail for `@.claude/skills/upmerge-master-to-demo/SKILL.md` Step 6f. The demo-shop pins `spryker/cypress-tests` to its own `master-demo` branch (`require-dev: "spryker/cypress-tests": "dev-master-demo"`), checked out under `tests/cypress-tests/`. It is a **separate repo** with its own `master`/`master-demo`; the demo-shop upmerge doesn't touch it, so upmerge it explicitly.

Log each sub-step: `[upmerge 6f-1/12] DONE  target=<hash>`; `[upmerge 6f-3/12] DONE  merged <hash> into cypress master-demo`; `[upmerge 6f-4/12] DONE  gate PASS`; `[upmerge 6f-5/12] DONE  cy:demo green, pushed cypress master-demo@<tip>, demo-shop re-pinned` (or `BLOCKED  cypress push denied — needs user authorization`).

## The rule: merge by HASH, never cypress master's tip

Advance cypress `master-demo` to **exactly the cypress commit the merged demo-shop version pins — and no further**. Cypress `master` is usually dozens of commits ahead of that pin (64 ahead in one recent case); merging its tip puts the demo cypress branch ahead of the shop → flaky/false failures. The pinned hash is the upper bound.

The correct version is the one pinned by the master commit already merged into `master-demo`. On the active upmerge branch (after Step 4), that's the branch's own `composer.lock` at `HEAD`.

## 6f-1 — Determine the target hash

```bash
# On the upmerge feature branch (Step 4 already merged master in):
git show HEAD:composer.lock | python3 -c "import json,sys; d=json.load(sys.stdin); p=[x for x in d['packages']+d['packages-dev'] if x['name']=='spryker/cypress-tests'][0]; print('target:', p['source']['reference'])"
```

If validating `master-demo` standalone (not mid-merge), read the merge-base pin instead:

```bash
MB=$(git merge-base master master-demo)
git show "${MB}:composer.lock" | python3 -c "import json,sys; d=json.load(sys.stdin); p=[x for x in d['packages']+d['packages-dev'] if x['name']=='spryker/cypress-tests'][0]; print('target:', p['source']['reference'])"
```

Call it `TARGET_CYPRESS_HASH`.

## 6f-2 — Sync the cypress repo and check the relationship

```bash
cd tests/cypress-tests
git fetch origin
git rev-parse master-demo
git log --oneline -1 <TARGET_CYPRESS_HASH>

# Already contained? Then master-demo is at/ahead of the pin — nothing to merge.
git merge-base --is-ancestor <TARGET_CYPRESS_HASH> master-demo && echo "already in master-demo — skip to 6f-5" || echo "merge required"
# How far cypress master is ahead of target (these must NOT be pulled in):
git log --oneline <TARGET_CYPRESS_HASH>..origin/master | wc -l
```

If already contained, record "cypress master-demo already at/ahead of pin — no upmerge needed" and skip to 6f-5 (still run `cy:demo`).

## 6f-3 — Merge the target HASH into cypress master-demo

```bash
cd tests/cypress-tests
git checkout master-demo
git merge <TARGET_CYPRESS_HASH> --no-ff -m "Upmerge cypress master (@<TARGET_CYPRESS_HASH>) into master-demo for <TICKET-or-date>"
```

Use the hash, not `git merge master`. Conflict policy: cypress `master-demo` carries the demo-only specs (`cypress/e2e/demo/` and demo fixtures/config) — keep the master-demo side there; take the incoming side for shared/core specs and helpers up to the pinned hash.

## 6f-4 — Quality gate (confirm you didn't merge ahead)

```bash
# Mid-upmerge, BEFORE the 6f-5 re-pin commit — supply the pin explicitly:
TARGET_HASH=<TARGET_CYPRESS_HASH> sh .claude/skills/upmerge-master-to-demo/check-cypress-not-ahead.sh
# AFTER the re-pin commit is on HEAD:
DEMOSHOP_REF=HEAD sh .claude/skills/upmerge-master-to-demo/check-cypress-not-ahead.sh
```

Exit 0 = pass, 1 = fail, 2 = setup error. Run it one of these two ways locally — the **plain, no-env** form derives the pin from `merge-base(master, master-demo)`, which on a feature branch points at the pre-upmerge commit and therefore reports a spurious "AHEAD by N" fail. That plain form is only authoritative in CI or once the PR is merged. Confirm a suspected failure with the two underlying facts:

```bash
cd tests/cypress-tests
git merge-base --is-ancestor <TARGET_CYPRESS_HASH> master-demo                       # pin is in master-demo
comm -12 <(git rev-list master-demo --not <TARGET_CYPRESS_HASH> | sort) \
         <(git rev-list <TARGET_CYPRESS_HASH>..origin/master | sort)                 # must print nothing
```

If a genuine failure: "ahead" → reset cypress `master-demo` to its pre-merge tip and redo 6f-3 with the hash; "behind" → the merge didn't reach the pin, redo 6f-3.

## 6f-5 — Run cy:demo, push, re-pin

**Push precondition (both, in order):** (1) `cy:demo` is green, (2) the gate reports pass. Only then push.

```bash
cd tests/cypress-tests
ENV_REPOSITORY_ID=b2b-mp ENV_IS_SSP_ENABLED=true npm run cy:demo    # all specs must pass
git push origin master-demo                                         # protected shared branch — see below
```

The push to cypress `master-demo` targets a **protected shared branch and is a hard stop for autonomous runs**: the auto-mode classifier denies it. Prefer the pre-authorized wrapper `sh .claude/skills/upmerge-master-to-demo/push-cypress-master-demo.sh` (it re-runs the gate then pushes). If both the direct push and the wrapper are denied, this is the one place the workflow cannot self-complete — leave the demo-shop pinned to the previous origin cypress ref (still valid/CI-resolvable), record the pending push in the PR body and final report, and make the re-pin a follow-up.

Capture the new cypress `master-demo` tip, then re-pin the demo-shop:

```bash
# demo-shop repo root, upmerge feature branch:
composer update spryker/cypress-tests --lock --no-install --ignore-platform-reqs
python3 -c "import json; d=json.load(open('composer.lock')); p=[x for x in d['packages']+d['packages-dev'] if x['name']=='spryker/cypress-tests'][0]; print('now pins:', p['version'], p['source']['reference'])"
git add composer.lock
git commit -m "chore(composer): re-pin spryker/cypress-tests to upmerged master-demo for <TICKET-or-date>"
```

Record in the PR body: the `TARGET_CYPRESS_HASH` merged, the new cypress `master-demo` tip pushed (or "push pending — user authorization required"), the re-pin, and confirmation no newer-than-target cypress-master commits leaked in.
