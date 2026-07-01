#!/usr/bin/env bash
# push-cypress-master-demo.sh
#
# Dedicated, narrowly-scoped push for Step 6f-5 of the upmerge-master-to-demo skill.
# Pushes the upmerged cypress `master-demo` branch to the external spryker/cypress-tests repo.
#
# WHY A WRAPPER: this exists so a settings allow-rule can pre-authorize ONLY this push
# (this script by name) for fully-automated upmerge runs, instead of a broad
# `git push origin master-demo` rule that would also match the demo-shop repo. The script
# hard-codes the cypress-tests dir, the branch, and re-runs the SAME quality gates the skill
# requires before any push — so even when pre-authorized, it refuses to push a bad branch.
#
# Exit codes: 0 pushed (or already up to date) · 1 a precondition/gate failed · 2 setup error.

set -euo pipefail

# --- locate repos -----------------------------------------------------------
# Resolve the demo-shop repo root from this script's location (.claude/skills/upmerge-master-to-demo/).
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEMOSHOP_DIR="${DEMOSHOP_DIR:-$(cd "$SCRIPT_DIR/../../.." && pwd)}"
CYPRESS_DIR="${CYPRESS_DIR:-$DEMOSHOP_DIR/tests/cypress-tests}"
BRANCH="master-demo"

die() { echo "ERROR: $*" >&2; exit 2; }
fail() { echo "GATE FAILED: $*" >&2; exit 1; }

[ -d "$CYPRESS_DIR/.git" ] || die "cypress repo not found at $CYPRESS_DIR"

# --- resolve TARGET pin (the cypress ref the MERGED demo-shop lock pins) -----
# Read from the demo-shop working-tree lock (the upmerge branch, mid-flow), fall back to HEAD.
TARGET_HASH="${TARGET_HASH:-$(
  python3 - "$DEMOSHOP_DIR/composer.lock" <<'PY'
import json,sys
d=json.load(open(sys.argv[1]))
p=[x for x in d['packages']+d.get('packages-dev',[]) if x['name']=='spryker/cypress-tests'][0]
print(p['source']['reference'])
PY
)}"
[ -n "$TARGET_HASH" ] || die "could not resolve TARGET cypress hash from composer.lock"

# --- GATE 1: cypress master-demo must be on the local branch and current ----
cd "$CYPRESS_DIR"
CUR="$(git branch --show-current)"
[ "$CUR" = "$BRANCH" ] || die "cypress repo is on '$CUR', expected '$BRANCH'"

# --- GATE 2: the not-ahead / not-behind quality gate (authoritative form) ---
# Reuse the bundled gate with TARGET_HASH so it can't false-fail on the feature branch.
GATE="$SCRIPT_DIR/check-cypress-not-ahead.sh"
[ -x "$GATE" ] || die "quality gate script not found/executable at $GATE"
echo ">> Running quality gate (TARGET_HASH=$TARGET_HASH) before push..."
if ! TARGET_HASH="$TARGET_HASH" DEMOSHOP_DIR="$DEMOSHOP_DIR" CYPRESS_DIR="$CYPRESS_DIR" sh "$GATE" >/tmp/cypress-gate-prepush.log 2>&1; then
  cat /tmp/cypress-gate-prepush.log >&2
  fail "check-cypress-not-ahead.sh did not pass — refusing to push. (Fix Step 6f-3, do NOT force.)"
fi
echo ">> Quality gate PASS."

# NOTE: `npm run cy:demo` (the other push precondition) is run by the skill in Step 6f-5 step 1
# BEFORE calling this script; it is intentionally not re-run here (it needs the live app + minutes).
# This script enforces the deterministic git-level gate; the skill is responsible for the cy:demo gate.

# --- push -------------------------------------------------------------------
echo ">> Pushing cypress '$BRANCH' to origin..."
git push origin "$BRANCH"
echo ">> Pushed. New $BRANCH tip: $(git rev-parse "$BRANCH")"
