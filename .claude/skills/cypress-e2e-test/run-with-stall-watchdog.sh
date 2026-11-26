#!/usr/bin/env bash
#
# Run a long test command (Cypress, Codeception, ...) with a STALL watchdog.
#
# Why: a headless Cypress run can hang forever with no timeout and no new output,
# for example when the page opens a native window.confirm()/alert() that blocks the
# browser. Cypress's own command timeouts never fire in that state. Without this
# guard an unattended run waits for hours.
#
# The watchdog checks the size of the log file. When the log does not grow for
# STALL_MINUTES, it prints the last spec and the last test lines, kills the whole
# process group (Cypress + its Chrome), and exits with code 124.
#
# Usage (from the directory where the command must run):
#   STALL_MINUTES=10 sh <path>/run-with-stall-watchdog.sh <log-file> -- npm run cy:demo
#   STALL_MINUTES=10 sh <path>/run-with-stall-watchdog.sh <log-file> -- npx cypress run --spec "<spec>" --headless --browser chrome
#
# Env:
#   STALL_MINUTES  minutes without new output before the run counts as stuck (default 10).
#                  Use a higher value (15) for @demo-full real AI provider runs.
#   POLL_SECONDS   check interval (default 30).
#
# Exit codes: the exit code of the command, or 124 when the watchdog killed a stuck run.

set -u

LOG="${1:?usage: run-with-stall-watchdog.sh <log-file> -- <command...>}"
shift
[ "${1:-}" = "--" ] && shift
[ "$#" -gt 0 ] || { echo "run-with-stall-watchdog: no command given" >&2; exit 2; }

STALL_MINUTES="${STALL_MINUTES:-10}"
POLL_SECONDS="${POLL_SECONDS:-30}"
STALL_SECONDS=$((STALL_MINUTES * 60))

: > "$LOG"

# Job control puts the background command in its own process group, so that one kill
# also stops the Cypress and Chrome child processes.
set -m
"$@" > "$LOG" 2>&1 &
PID=$!
set +m

last_size=-1
last_change=$(date +%s)

while kill -0 "$PID" 2>/dev/null; do
    sleep "$POLL_SECONDS"
    size=$(wc -c < "$LOG" | tr -d ' ')
    now=$(date +%s)
    if [ "$size" != "$last_size" ]; then
        last_size=$size
        last_change=$now
        continue
    fi
    idle=$((now - last_change))
    if [ "$idle" -ge "$STALL_SECONDS" ]; then
        echo "STALLED: no new output for ${idle}s (limit ${STALL_SECONDS}s). Killing the run."
        echo "--- last spec ---"
        tr -d '\033' < "$LOG" | grep -E 'Running:' | tail -1
        echo "--- last test lines (the stuck test is the NEXT one in the spec) ---"
        tr -d '\033' < "$LOG" | grep -E '✓|✖|[0-9]+\) ' | tail -3
        kill -TERM -- "-$PID" 2>/dev/null || kill -TERM "$PID" 2>/dev/null
        sleep 5
        kill -KILL -- "-$PID" 2>/dev/null || true
        # Cypress can leave its app process behind when its parent dies.
        pkill -f 'Cypress.app/Contents/MacOS/Cypress' 2>/dev/null || true
        pkill -f '/Cypress/[0-9.]*/Cypress/Cypress' 2>/dev/null || true
        exit 124
    fi
done

wait "$PID"
exit $?
