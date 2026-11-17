#!/usr/bin/env bash
#
# Puts the environment back to a database snapshot, with storages that match it.
#
# A database restore alone leaves the key-value store and search holding the data of another state, so
# the next run would measure updates to keys that should not exist. Steps:
#   1. db-snapshot.sh restore <name> (the current tables go to a trash__ database, nothing is dropped)
#   2. empty the key-value store, delete the search indices and recreate them with their mappings
#   3. publish:trigger-events and sync:data rebuild storage and search from the restored database
#   4. positive control: keys and documents exist again, no consumed queue holds messages
#
# Usage: tools/ps-metrics/reset-to-snapshot.sh <snapshot name>
# Needs the scheduler running (it drains the sync messages). Environment as for run-baseline.sh plus
# DB_CONTAINER for db-snapshot.sh.

set -euo pipefail

name="${1:?snapshot name required}"
CONTAINER="${CLI_CONTAINER:-spryker_cli_1}"
MAX_WAIT_S="${MAX_WAIT_S:-3600}"
here="$(dirname "$0")"

fail() { printf 'reset-to-snapshot: %s\n' "$1" >&2; exit 1; }
say()  { printf '\n=== %s ===\n' "$1"; }
work_total() {
    docker exec "${CONTAINER}" bash -lc "curl -s -u \${SPRYKER_BROKER_API_USERNAME:-spryker}:\${SPRYKER_BROKER_API_PASSWORD:-secret} http://broker:15672/api/queues" \
        | python3 -c 'import sys,json;ex=set(sys.argv[1].split(","));print(sum(q.get("messages",0) for q in json.load(sys.stdin) if not q["name"].endswith(".error") and q["name"] not in ex))' "${WORK_QUEUE_EXCLUDE:-scheduler-jobs}"
}

# See run-baseline.sh: connection logging at info stalls AMQP handshakes past the 3 s connect timeout.
docker exec "${BROKER_CONTAINER:-${CONTAINER%_cli_1}_broker_1}" rabbitmqctl -q set_log_level "${BROKER_LOG_LEVEL:-warning}" || fail "could not set the broker log level"

say "1. Database"
[ "$(work_total)" = "0" ] || fail "consumed queues hold messages; wait for the drain before resetting"
"${here}/db-snapshot.sh" restore "${name}"

say "2. Storages"
# FLUSHALL also removes the scheduler's locks: newer releases keep the symfonymessenger consumer lock in
# the key-value store, and the running consumer then dies with LockConflictedException. Jenkins starts it
# again within a minute; the sync drain below waits for it.
docker exec "${CONTAINER}" bash -lc "redis-cli -h key_value_store -t 30 FLUSHALL" >/dev/null || fail "FLUSHALL failed"
docker exec "${CONTAINER}" bash -lc "curl -s -X DELETE 'http://search:9200/spryker_*'" >/dev/null || fail "deleting search indices failed"
# Deleting an index deletes its mapping; without setup the first document would map fields dynamically.
# Checked by its marker: `docker/sdk cli` exits 0 even when the command failed, and the former
# `|| true` with an index count let a setup pass that had failed for three of five stores (2026-11-16).
out=$(docker/sdk cli "vendor/bin/console search:setup:sources && echo STEP-OK" 2>&1 || true)
printf '%s\n' "${out}" | grep -q 'STEP-OK' || { printf '%s\n' "${out}" | grep -vE '^#[0-9]+ ' | tail -15 >&2; fail "search:setup:sources failed"; }
n=$(docker exec "${CONTAINER}" bash -lc "curl -s 'http://search:9200/_cat/indices/spryker_*?h=index'" | grep -c . || true)
[ "${n:-0}" -gt 0 ] || fail "no search index after search:setup:sources"
printf '  key-value store emptied, %s search indices recreated\n' "${n}"

say "3. publish:trigger-events and sync:data"
# sync:data alone does not rebuild product search: b2b has no product page resource in it (checked
# 2026-11-04, the page indices stayed at 0). The installer's way is a full publish from the database,
# then sync:data for the storage-only resources.
for cmd in "publish:trigger-events" "sync:data"; do
    out=$(docker/sdk cli "vendor/bin/console ${cmd} && echo STEP-OK" 2>&1 || true)
    printf '%s\n' "${out}" | grep -q 'STEP-OK' || { printf '%s\n' "${out}" | tail -20 >&2; fail "${cmd} failed"; }
done
quiet=0
for _ in $(seq 1 "${MAX_WAIT_S}"); do
    if [ "$(work_total)" = "0" ]; then quiet=$((quiet + 1)); else quiet=0; fi
    [ "${quiet}" -ge 5 ] && break
    sleep 1
done
[ "${quiet}" -ge 5 ] || fail "sync messages did not drain within ${MAX_WAIT_S} s"

say "4. Positive control"
# Search documents appear after the queues are empty (refresh lag): right after the drain the page index
# showed 40 of about 4,800. Read until two counts 10 s apart agree.
# Exact top-level documents from _count; _cat docs.count includes nested documents and moves with merges.
es_count() { docker exec "${CONTAINER}" bash -lc "curl -s 'http://search:9200/spryker_*/_count'" | python3 -c 'import sys,json; print(json.load(sys.stdin).get("count", 0))'; }
prev=-1
for _ in $(seq 1 60); do
    cur=$(es_count)
    # Two zeros are not settled: they were read before the first document arrived.
    [ "${cur}" = "${prev}" ] && [ "${cur}" != "0" ] && break
    prev="${cur}"
    sleep 10
done
kv=$("${here}/kv-keys.sh" --settle --container "${CONTAINER}" | tr -d ' \r')
es=$(es_count)
printf '  key-value keys: %s, search docs: %s\n' "${kv}" "${es}"
[ "${kv}" -gt 0 ] && [ "${es}" -gt 0 ] || fail "storages are empty after sync:data"
CLI_CONTAINER="${CONTAINER}" "${here}/store-setup.sh" check || fail "a store cannot serve Yves and Glue after the rebuild"
printf 'reset-to-snapshot: %s restored, storages rebuilt\n' "${name}"
