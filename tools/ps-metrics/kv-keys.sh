#!/usr/bin/env bash
#
# Prints the number of keys in the key-value store, for Valkey/Redis and for Kvrocks.
#
# Valkey/Redis: the sum of keys over every database in INFO keyspace. Never DBSIZE alone and never a
# bare --scan, which sees database 0 only while the application writes to database 1.
# Kvrocks: INFO keyspace and DBSIZE both report the last finished background scan, and 0 before the
# first one (checked 2026-11-04 on apache/kvrocks:2.10.1: one key written, keyspace said keys=0 until
# DBSIZE SCAN ran). Kvrocks also ignores SELECT, so there is one keyspace. This script starts a scan;
# with --settle it waits until two counts 5 s apart agree, so the figure is current, not stale.
#
# Usage: kv-keys.sh [--settle] [--container NAME]

set -euo pipefail

CONTAINER="${CLI_CONTAINER:-spryker_cli_1}"
SETTLE=0
while [ $# -gt 0 ]; do
    case "$1" in
        --settle) SETTLE=1; shift ;;
        --container) CONTAINER="$2"; shift 2 ;;
        *) echo "Unknown argument: $1" >&2; exit 2 ;;
    esac
done

kv() { docker exec "${CONTAINER}" bash -lc "redis-cli -h key_value_store $*" 2>/dev/null; }
count() { kv INFO keyspace | sed -n 's/^db[0-9]*:keys=\([0-9]*\).*/\1/p' | awk '{s+=$1} END {print s+0}'; }

if ! kv INFO server | grep -q '^kvrocks_version'; then
    count
    exit 0
fi
kv DBSIZE SCAN >/dev/null || true
if [ "${SETTLE}" = "0" ]; then
    count
    exit 0
fi
prev=-1
for _ in $(seq 1 120); do
    sleep 5
    kv DBSIZE SCAN >/dev/null || true
    cur=$(count)
    [ "${cur}" = "${prev}" ] && [ "${cur}" != "0" ] && break
    prev="${cur}"
done
printf '%s\n' "${cur}"
