#!/usr/bin/env bash
#
# Quality gate: assert the cypress-tests `master-demo` branch matches the cypress
# version pinned by the MASTER VERSION ALREADY MERGED INTO the demo-shop `master-demo`
# — and is not ahead of it.
#
# WHY THE PIN COMES FROM THE MERGE-BASE, not from master's tip:
#   The demo-shop pins `spryker/cypress-tests` to a commit in composer.lock
#   (packages[spryker/cypress-tests].source.reference). But demo-shop `master-demo`
#   usually does NOT contain master's latest — it contains master only up to the point
#   it was last upmerged. The cypress version master-demo should match is the one pinned
#   by the LATEST master commit master-demo actually has = `git merge-base master master-demo`.
#   Using master's TIP pin instead would demand a cypress version for shop code that
#   master-demo doesn't have yet (the inverse skew). Using the merge-base pin is correct.
#
#   Then, in the cypress repo: cypress `master-demo` must CONTAIN that pin and must NOT
#   absorb any cypress-`master` commit NEWER than it (that would make the demo tests run
#   ahead of the demo-shop version -> flaky/false failures).
#
# WHAT IT CHECKS (in the cypress repo, against the merge-base pin = TARGET):
#   1. TARGET is an ancestor of cypress master-demo   (master-demo includes the pin)
#   2. No commit reachable from master-demo and ahead of TARGET on cypress master
#      leaked in                                       (master-demo is not ahead)
#
# USAGE:
#   .claude/skills/upmerge-master-to-demo/check-cypress-not-ahead.sh
#
# ENV OVERRIDES (all optional — sane defaults below):
#   DEMOSHOP_DIR     demo-shop repo root             (default: git toplevel of CWD)
#   CYPRESS_DIR      cypress-tests checkout           (default: $DEMOSHOP_DIR/tests/cypress-tests)
#   DEMOSHOP_MASTER  demo-shop master branch          (default: master)
#   DEMOSHOP_DEMO    demo-shop demo branch            (default: master-demo)
#   DEMOSHOP_REF     explicit demo-shop ref to read the pin from; OVERRIDES the
#                    merge-base default (e.g. set to HEAD on the upmerge branch)
#   CYPRESS_DEMO     cypress demo branch              (default: master-demo)
#   CYPRESS_MASTER   cypress upstream branch          (default: origin/master)
#   TARGET_HASH      override the pinned hash directly (default: read from composer.lock)
#   NO_FETCH         set to 1 to skip `git fetch`      (default: fetch)
#
# EXIT CODES: 0 = gate passes, 1 = gate fails (ahead / pin missing), 2 = setup error.
#
# NOTE: this script requires bash (uses bash arrays/redirection). If invoked as
# `sh check-cypress-not-ahead.sh` under a POSIX sh (dash), it re-execs itself with bash.

# Re-exec under bash if we were started by a non-bash shell (e.g. `sh script.sh`).
if [ -z "${BASH_VERSION:-}" ]; then
  exec bash "$0" "$@"
fi

set -euo pipefail

red()   { printf '\033[31m%s\033[0m\n' "$*"; }
green() { printf '\033[32m%s\033[0m\n' "$*"; }
yellow(){ printf '\033[33m%s\033[0m\n' "$*"; }
bold()  { printf '\033[1m%s\033[0m\n'  "$*"; }
dim()   { printf '\033[2m%s\033[0m\n'  "$*"; }
rule()  { printf '\033[2m%s\033[0m\n'  "────────────────────────────────────────────────────────────────────"; }
die()   { red "ERROR: $*"; exit 2; }

# Show a commit as "<short> <subject>" or a friendly note if it can't be resolved.
show_commit() { git -C "$CYPRESS_DIR" log --oneline -1 "$1" 2>/dev/null || echo "$1 (not resolvable)"; }

DEMOSHOP_MASTER="${DEMOSHOP_MASTER:-master}"
DEMOSHOP_DEMO="${DEMOSHOP_DEMO:-master-demo}"
CYPRESS_DEMO="${CYPRESS_DEMO:-master-demo}"
CYPRESS_MASTER="${CYPRESS_MASTER:-origin/master}"

# Resolve the demo-shop repo root.
if [ -n "${DEMOSHOP_DIR:-}" ]; then
  DEMOSHOP_DIR="$(cd "$DEMOSHOP_DIR" && pwd)"
else
  DEMOSHOP_DIR="$(git -C "$(pwd)" rev-parse --show-toplevel 2>/dev/null)" \
    || die "not inside a git repo and DEMOSHOP_DIR not set"
  # If we're inside the cypress checkout, walk up to the demo-shop root.
  case "$DEMOSHOP_DIR" in
    */tests/cypress-tests) DEMOSHOP_DIR="${DEMOSHOP_DIR%/tests/cypress-tests}" ;;
  esac
fi
CYPRESS_DIR="${CYPRESS_DIR:-$DEMOSHOP_DIR/tests/cypress-tests}"

[ -f "$DEMOSHOP_DIR/composer.lock" ] || die "no composer.lock in demo-shop dir: $DEMOSHOP_DIR"
[ -d "$CYPRESS_DIR/.git" ] || die "cypress checkout is not a git repo: $CYPRESS_DIR"

# --- 1. Determine PIN_REF = the demo-shop ref whose composer.lock we read -------------
# Default: the merge-base of master and master-demo = the latest MASTER version already
# merged into master-demo. Override with DEMOSHOP_REF (e.g. HEAD) or TARGET_HASH.
read_cypress_ref() {  # $1 = demo-shop git ref
  git -C "$DEMOSHOP_DIR" show "$1:composer.lock" 2>/dev/null \
    | python3 -c "import json,sys; d=json.load(sys.stdin); p=[x for x in d['packages']+d.get('packages-dev',[]) if x['name']=='spryker/cypress-tests']; sys.exit('spryker/cypress-tests not found in lock') if not p else print(p[0]['source']['reference'])"
}

if [ -n "${TARGET_HASH:-}" ]; then
  PIN_REF="(TARGET_HASH override)"
  TARGET="$TARGET_HASH"
else
  if [ -n "${DEMOSHOP_REF:-}" ]; then
    PIN_REF="$DEMOSHOP_REF"
  else
    git -C "$DEMOSHOP_DIR" rev-parse --verify "$DEMOSHOP_MASTER" >/dev/null 2>&1 \
      || die "demo-shop branch '$DEMOSHOP_MASTER' not found"
    git -C "$DEMOSHOP_DIR" rev-parse --verify "$DEMOSHOP_DEMO" >/dev/null 2>&1 \
      || die "demo-shop branch '$DEMOSHOP_DEMO' not found"
    PIN_REF="$(git -C "$DEMOSHOP_DIR" merge-base "$DEMOSHOP_MASTER" "$DEMOSHOP_DEMO")" \
      || die "could not compute merge-base of '$DEMOSHOP_MASTER' and '$DEMOSHOP_DEMO'"
  fi
  TARGET="$(read_cypress_ref "$PIN_REF")" \
    || die "could not read spryker/cypress-tests reference from $PIN_REF:composer.lock"
fi
[ -n "$TARGET" ] || die "empty target hash"

# --- 2. Make sure refs are present in the cypress repo (before we display anything) ---
if [ "${NO_FETCH:-0}" != "1" ]; then
  git -C "$CYPRESS_DIR" fetch origin --quiet || yellow "warning: git fetch failed, using local refs"
fi
git -C "$CYPRESS_DIR" cat-file -e "${TARGET}^{commit}" 2>/dev/null \
  || die "target commit $TARGET not found in cypress repo (fetch it, or it is the wrong hash)"
git -C "$CYPRESS_DIR" rev-parse --verify "$CYPRESS_DEMO" >/dev/null 2>&1 \
  || die "cypress branch '$CYPRESS_DEMO' not found"
git -C "$CYPRESS_DIR" rev-parse --verify "$CYPRESS_MASTER" >/dev/null 2>&1 \
  || die "cypress branch '$CYPRESS_MASTER' not found"

# Describe where the PIN came from, for the banner.
if [ -n "${TARGET_HASH:-}" ]; then
  PIN_SOURCE_DESC="explicit TARGET_HASH override"
elif [ -n "${DEMOSHOP_REF:-}" ]; then
  PIN_SOURCE_DESC="demo-shop ref '$DEMOSHOP_REF' (composer.lock)"
else
  PIN_SOURCE_DESC="merge-base of demo-shop '$DEMOSHOP_MASTER' & '$DEMOSHOP_DEMO' = latest master merged into '$DEMOSHOP_DEMO'"
fi

# --- 3. Banner: explain what we check and show the reference points -------------------
rule
bold "QUALITY GATE — is cypress '$CYPRESS_DEMO' in sync with the demo-shop's merged version?"
rule
echo "What this checks, in plain words:"
echo "  demo-shop '$DEMOSHOP_DEMO' contains MASTER only up to where it was last upmerged,"
echo "  NOT master's tip. The cypress version it should match is the one pinned by that"
echo "  merged master point = git merge-base '$DEMOSHOP_MASTER' '$DEMOSHOP_DEMO' (the PIN)."
echo "  Cypress '$CYPRESS_DEMO' must CONTAIN that PIN (else BEHIND),"
echo "  and must NOT contain any cypress-'${CYPRESS_MASTER##*/}' commit NEWER than the PIN (else AHEAD)."
echo "  AHEAD is the danger: the demo would run tests for shop code that doesn't exist yet."
echo
echo "Where the PIN comes from:"
echo "  $PIN_SOURCE_DESC"
if [ -z "${TARGET_HASH:-}" ] && [ -z "${DEMOSHOP_REF:-}" ]; then
  printf '  %-38s : %s\n' "merged master commit (demo-shop)" "$(git -C "$DEMOSHOP_DIR" log --oneline -1 "$PIN_REF" 2>/dev/null)"
fi
echo
echo "The reference points (in the cypress repo):"
printf '  %-38s : %s\n' "PIN — cypress version that merged master pins" "$(show_commit "$TARGET")"
printf '  %-38s : %s\n' "cypress '$CYPRESS_DEMO' tip"                    "$(show_commit "$CYPRESS_DEMO")"
printf '  %-38s : %s\n' "cypress '$CYPRESS_MASTER' tip"                  "$(show_commit "$CYPRESS_MASTER")"
echo
echo "  Demo-only commits on '$CYPRESS_DEMO' (on top of the PIN — these are expected):"
DEMO_ONLY="$(git -C "$CYPRESS_DIR" rev-list "$CYPRESS_DEMO" --not "$TARGET" "$CYPRESS_MASTER")"
if [ -z "$DEMO_ONLY" ]; then
  dim "    (none — '$CYPRESS_DEMO' is exactly at the PIN)"
else
  while IFS= read -r sha; do [ -n "$sha" ] && dim "    + $(show_commit "$sha")"; done <<< "$DEMO_ONLY"
fi
echo "  Commits on cypress '${CYPRESS_MASTER##*/}' that are NEWER than the PIN (must stay OUT of '$CYPRESS_DEMO'): $(git -C "$CYPRESS_DIR" rev-list --count "$TARGET..$CYPRESS_MASTER")"
echo

FAIL=0
BEHIND=0
AHEAD=0

bold "Checks:"

# --- Check 1: is the PIN contained in cypress master-demo? (not BEHIND) ----------------
if git -C "$CYPRESS_DIR" merge-base --is-ancestor "$TARGET" "$CYPRESS_DEMO"; then
  green "  [PASS] Check 1 — NOT BEHIND: the PIN is contained in '$CYPRESS_DEMO'."
  dim   "         (the demo branch includes the cypress version the shop expects)"
else
  red   "  [FAIL] Check 1 — BEHIND: the PIN is NOT in '$CYPRESS_DEMO'."
  dim   "         The demo branch is missing the commit the shop pins."
  FAIL=1; BEHIND=1
fi

# --- Check 2: did any cypress-master commit NEWER than the PIN leak into master-demo? --
# Set intersection: (commits in master-demo but not in PIN) ∩ (commits ahead of PIN on master)
_tmp_md="$(mktemp)"; _tmp_ahead="$(mktemp)"
trap 'rm -f "$_tmp_md" "$_tmp_ahead"' EXIT
git -C "$CYPRESS_DIR" rev-list "$CYPRESS_DEMO" --not "$TARGET" | sort > "$_tmp_md"
git -C "$CYPRESS_DIR" rev-list "$TARGET..$CYPRESS_MASTER"      | sort > "$_tmp_ahead"
LEAK="$(comm -12 "$_tmp_md" "$_tmp_ahead")"
LEAK_COUNT="$(printf '%s' "$LEAK" | grep -c . || true)"

if [ "$LEAK_COUNT" -eq 0 ]; then
  green "  [PASS] Check 2 — NOT AHEAD: no cypress-'${CYPRESS_MASTER##*/}' commit newer than the PIN is in '$CYPRESS_DEMO'."
  dim   "         (the demo branch does not run ahead of the shop version)"
else
  red   "  [FAIL] Check 2 — AHEAD: $LEAK_COUNT cypress-'${CYPRESS_MASTER##*/}' commit(s) NEWER than the PIN are in '$CYPRESS_DEMO':"
  while IFS= read -r sha; do
    [ -n "$sha" ] && red "             $(show_commit "$sha")"
  done <<< "$LEAK"
  dim   "         These commits exist on cypress '${CYPRESS_MASTER##*/}' AFTER the PIN, but should not"
  dim   "         be in the demo branch — they test shop code the merged demo-shop version doesn't have yet."
  FAIL=1; AHEAD=1
fi

echo
rule
# --- Conclusion -----------------------------------------------------------------------
if [ "$FAIL" -eq 0 ]; then
  green "CONCLUSION: ✅ PASS"
  echo  "  Cypress '$CYPRESS_DEMO' is exactly in sync with the merged demo-shop version's pin:"
  echo  "  it contains the PIN and adds only demo-only commits on top — nothing ahead."
  echo  "  Safe to push '$CYPRESS_DEMO' and re-pin the demo-shop to it (Step 6f-5)."
  rule
  exit 0
fi

red "CONCLUSION: ❌ FAIL"
if [ "$BEHIND" -eq 1 ]; then
  echo "  • BEHIND — '$CYPRESS_DEMO' does not contain the PIN."
  echo "    FIX: merge the PIN hash into '$CYPRESS_DEMO' (Step 6f-3):"
  echo "         cd $CYPRESS_DIR && git checkout $CYPRESS_DEMO && git merge $TARGET --no-ff"
fi
if [ "$AHEAD" -eq 1 ]; then
  echo "  • AHEAD — '$CYPRESS_DEMO' contains $LEAK_COUNT commit(s) newer than the PIN (listed above)."
  echo "    Cause: someone ran 'git merge ${CYPRESS_MASTER##*/}' instead of merging the PIN hash."
  echo "    FIX: reset '$CYPRESS_DEMO' to its pre-merge tip, then re-merge the PIN HASH (not '${CYPRESS_MASTER##*/}'):"
  echo "         cd $CYPRESS_DIR && git checkout $CYPRESS_DEMO && git merge $TARGET --no-ff"
fi
echo "  Do NOT push '$CYPRESS_DEMO' or re-pin the demo-shop until this gate is green."
rule
exit 1
