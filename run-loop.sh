#!/usr/bin/env bash
set -euo pipefail

# Config (env vars let you tune without redeploying)
: "${CMD:=php vendor/bin/console q:w:s -vv}"   # replace with your command
: "${SLEEP_SECS:=5}"                       # pause between runs
: "${RUN_TIMEOUT:=0}"                      # 0 = no timeout; else seconds

# Graceful shutdown when Heroku sends SIGTERM
stop=false
trap 'stop=true' SIGTERM SIGINT

while [ "$stop" = false ]; do
  if [ "$RUN_TIMEOUT" -gt 0 ]; then
    # kill the command if it exceeds the timeout
    timeout --preserve-status "$RUN_TIMEOUT" bash -lc "$CMD" || true
  else
    bash -lc "$CMD" || true
  fi

  # Avoid tight loops if command finishes immediately
  sleep "$SLEEP_SECS"
done

echo "Shutting down gracefully..."
