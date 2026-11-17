#!/usr/bin/env bash
#
# SOL-629 baseline run.
#
# Executes the whole measurement in one uninterrupted, scripted window, because the 2026-09-29 run
# showed that a hand-driven sequence gets contaminated: the scheduler drains queues behind you, and
# a window opened over an existing backlog makes the aggregates meaningless.
#
# Fails fast on a precondition rather than producing numbers that cannot be trusted.

set -euo pipefail

RUN_ID="${RUN_ID:-$(date +%Y%m%d-%H%M%S)}"
OUT_DIR="${OUT_DIR:-data/metrics/${RUN_ID}}"
CONTAINER="${CLI_CONTAINER:-spryker_cli_1}"
BROKER_HOST="${RABBITMQ_HOST:-queue.spryker.local}"
BROKER_PORT="${RABBITMQ_MANAGEMENT_PORT:-80}"
BROKER_USER="${RABBITMQ_USER:-spryker}"
BROKER_PASS="${RABBITMQ_PASS:-secret}"
IMPORT_CONFIG="${IMPORT_CONFIG:-data/import/local/full_EU.yml}"
# Contract C8: the fraction of the source rows this run imports. 1.0 is the full set. Below 1.0 the
# config must be a subset built by scale-import.py; without IMPORT_CONFIG the runner uses the subset's
# default location and fails if it was not built.
SCALE="${SCALE:-1.0}"
# MAX_WAIT_S: how long the drain and the samplers may run. 2 hours covers the demo catalogs; a large
# generated catalog needs more.
MAX_WAIT_S="${MAX_WAIT_S:-7200}"
# IMPORT_SPLIT=1 runs one data:import process per action of IMPORT_CONFIG, in file order, as one import
# stage. A large catalog does not fit one process: static buffers of the earlier importers stay in
# memory (the 1% ICP set ran out of 1 GB in one process). IMPORT_MEMORY_LIMIT sets php memory_limit
# for the import processes (for example -1); empty keeps the image default.
IMPORT_SPLIT="${IMPORT_SPLIT:-0}"
case "${IMPORT_SPLIT}" in
    0|1) ;;
    *) printf 'IMPORT_SPLIT must be 0 or 1, got %s\n' "${IMPORT_SPLIT}" >&2; exit 2 ;;
esac
IMPORT_MEMORY_LIMIT="${IMPORT_MEMORY_LIMIT:-}"
IMPORT_RETRIES="${IMPORT_RETRIES:-1}"
python3 -c 'import sys; v = float(sys.argv[1]); sys.exit(0 if 0 < v <= 1 else 1)' "${SCALE}" 2>/dev/null \
    || { printf 'SCALE must be a number in (0, 1], got %s\n' "${SCALE}" >&2; exit 2; }
if [ "${IMPORT_CONFIG}" = "data/import/local/full_EU.yml" ] && ! python3 -c 'import sys; sys.exit(0 if float(sys.argv[1]) == 1 else 1)' "${SCALE}"; then
    IMPORT_CONFIG="data/metrics/scaled/full_EU-$(python3 -c 'import sys; print(f"{float(sys.argv[1]):g}")' "${SCALE}")/import.yml"
fi
# PUBLISH_TRIGGER=0 skips the publish:trigger-events stage. That stage republishes every entity in the
# database, so on the update path a scaled run would still publish the full catalog there.
PUBLISH_TRIGGER="${PUBLISH_TRIGGER:-1}"
case "${PUBLISH_TRIGGER}" in
    0|1) ;;
    *) printf 'PUBLISH_TRIGGER must be 0 or 1, got %s\n' "${PUBLISH_TRIGGER}" >&2; exit 2 ;;
esac
# SYNC_DATA=0 skips the sync:data stage. That stage re-sends every storage and search row in the
# database, so it also covers the full catalog whatever the scale.
SYNC_DATA="${SYNC_DATA:-1}"
case "${SYNC_DATA}" in
    0|1) ;;
    *) printf 'SYNC_DATA must be 0 or 1, got %s\n' "${SYNC_DATA}" >&2; exit 2 ;;
esac
# INSTRUMENTED=0 runs the same stages without QUEUE_METRICS_ENABLED (A-20): the overhead baseline.
# Such a run writes no task, listener or import records, only stages, storage samples and meta.
INSTRUMENTED="${INSTRUMENTED:-1}"
case "${INSTRUMENTED}" in
    0|1) ;;
    *) printf 'INSTRUMENTED must be 0 or 1, got %s\n' "${INSTRUMENTED}" >&2; exit 2 ;;
esac

# IMPORT=0 skips the import: P&S alone on the data already in the database, the way it runs when data
# changes through the API or a custom importer. Use it with PUBLISH_TRIGGER=1; PUBLISH_RESOURCES limits
# publish:trigger-events to a comma-separated resource list (its -r option), for a cost per entity type.
IMPORT="${IMPORT:-1}"
case "${IMPORT}" in
    0|1) ;;
    *) printf 'IMPORT must be 0 or 1, got %s\n' "${IMPORT}" >&2; exit 2 ;;
esac
PUBLISH_RESOURCES="${PUBLISH_RESOURCES:-}"
if [ "${IMPORT}" = "0" ] && [ "${PUBLISH_TRIGGER}" = "0" ] && [ "${SYNC_DATA}" = "0" ]; then
    printf 'IMPORT=0 with PUBLISH_TRIGGER=0 and SYNC_DATA=0 measures nothing\n' >&2; exit 2
fi
if [ -n "${PUBLISH_RESOURCES}" ] && [ "${PUBLISH_TRIGGER}" = "0" ]; then
    printf 'PUBLISH_RESOURCES needs PUBLISH_TRIGGER=1\n' >&2; exit 2
fi

# SCHEDULER=1 measures the system as it runs in production: the scheduler is never paused, its regular
# Jenkins jobs start the queue workers with the regular settings, and they consume while the import and
# P&S stages run. The runner touches no worker and no queue: it waits until the queues are empty, starts
# the stages and waits until the queues are empty again. The first task start is read from the records. Workers started by the scheduler cannot get variables from the runner, so the metrics
# settings reach them through the module's settings file (SettingsFile), valid for SETTINGS_TTL seconds.
# A chunk size map cannot reach them either, so it is refused: the run uses the configured chunk sizes.
SCHEDULER="${SCHEDULER:-0}"
case "${SCHEDULER}" in
    0|1) ;;
    *) printf 'SCHEDULER must be 0 or 1, got %s\n' "${SCHEDULER}" >&2; exit 2 ;;
esac
if [ "${SCHEDULER}" = "1" ] && [ -n "${QUEUE_MESSAGE_CHUNK_SIZE_MAP:-}" ]; then
    printf 'QUEUE_MESSAGE_CHUNK_SIZE_MAP cannot reach scheduler workers; unset it for SCHEDULER=1\n' >&2; exit 2
fi
SETTINGS_FILE_IN_CONTAINER="${SETTINGS_FILE_IN_CONTAINER:-/tmp/queue-metrics.settings.json}"
# Valid for the whole run window: import and drain can each take up to MAX_WAIT_S. A fixed 3 hours cut
# the records of a 10% ICP run after 3 h. The runner removes the file at drain end and before a run.
SETTINGS_TTL="${SETTINGS_TTL:-$((2 * MAX_WAIT_S + 3600))}"

API="http://${BROKER_HOST}:${BROKER_PORT}/api"
CURL=(curl -sS -u "${BROKER_USER}:${BROKER_PASS}")

mkdir -p "${OUT_DIR}"

say()  { printf '\n=== %s ===\n' "$1"; }
fail() { printf '\nPRECONDITION FAILED: %s\n' "$1" >&2; exit 1; }

queue_total() {
    "${CURL[@]}" "${API}/queues" | python3 -c 'import sys,json;print(sum(q.get("messages",0) for q in json.load(sys.stdin)))'
}
# Error queues have no consumer, so waiting for them to empty never ends. The drain wait counts only
# queues a worker consumes; the error total is reported as its own figure.
# Queues that carry no P&S work are left out too: since 2026-11-06 b2b sends cron jobs through the
# scheduler-jobs queue every minute, so counting it would tie the drain end to the cron schedule.
WORK_QUEUE_EXCLUDE="${WORK_QUEUE_EXCLUDE:-scheduler-jobs}"
work_total() {
    "${CURL[@]}" "${API}/queues" | python3 -c 'import sys,json;ex=set(sys.argv[1].split(","));print(sum(q.get("messages",0) for q in json.load(sys.stdin) if not q["name"].endswith(".error") and q["name"] not in ex))' "${WORK_QUEUE_EXCLUDE}"
}
# Livelock: a consumed queue holds messages, gets no new acks, and its messages are redelivered, for
# LIVELOCK_S seconds. Seen on 2026-11-05: with QUEUE_WORKER_WAIT_LIMIT_ENABLED the worker kills a task that
# runs past about 89 s, the batch goes back to the head of the queue, and the next task takes it again;
# 25 h with no ack and nothing in error queues. livelock_check is called once a minute and returns 0 when
# a livelock is confirmed, after writing livelock.json.
LIVELOCK_S="${LIVELOCK_S:-900}"
LIVELOCK=0
LIVELOCK_STATE="${OUT_DIR}/.livelock-state.json"
livelock_check() {
    "${CURL[@]}" "${API}/queues" | python3 -c '
import json, sys, time
state_file, out_file, limit = sys.argv[1], sys.argv[2], int(sys.argv[3])
excluded = set(sys.argv[4].split(","))
now = time.time()
try:
    state = json.load(open(state_file))
except (OSError, ValueError):
    state = {}
new, stuck = {}, []
for q in json.load(sys.stdin):
    name = q["name"]
    if name.endswith(".error") or name in excluded or not q.get("messages"):
        continue
    stats = q.get("message_stats", {})
    acked, redelivered = stats.get("ack", 0), stats.get("redeliver", 0)
    old = state.get(name)
    if old and old["acked"] == acked:
        since = old["since"]
        if now - since >= limit and redelivered > old["redelivered_at_since"]:
            stuck.append({"queue": name, "messages": q["messages"], "acked": acked,
                          "redelivered_since": redelivered - old["redelivered_at_since"], "seconds": round(now - since)})
        new[name] = {"acked": acked, "since": since, "redelivered_at_since": old["redelivered_at_since"]}
    else:
        new[name] = {"acked": acked, "since": now, "redelivered_at_since": redelivered}
json.dump(new, open(state_file, "w"))
if stuck:
    json.dump({"detected_at": round(now), "limit_s": limit, "queues": stuck}, open(out_file, "w"))
    sys.exit(0)
sys.exit(1)' "${LIVELOCK_STATE}" "${OUT_DIR}/livelock.json" "${LIVELOCK_S}" "${WORK_QUEUE_EXCLUDE}" || return 1
    LIVELOCK=1
}
error_total() {
    "${CURL[@]}" "${API}/queues" | python3 -c 'import sys,json;print(sum(q.get("messages",0) for q in json.load(sys.stdin) if q["name"].endswith(".error")))'
}
consumer_total() {
    "${CURL[@]}" "${API}/queues" | python3 -c 'import sys,json;print(sum(q.get("consumers",0) for q in json.load(sys.stdin)))'
}

say "0. Preconditions"
docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$" || fail "container ${CONTAINER} is not running"
[ -f "${IMPORT_CONFIG}" ] || fail "import config ${IMPORT_CONFIG} not found (scaled: tools/ps-metrics/scale-import.py build --fraction ${SCALE})"
printf '  import config: %s (scale %s)\n' "${IMPORT_CONFIG}" "${SCALE}"
"${CURL[@]}" -o /dev/null -w '' "${API}/overview" || fail "broker management API not reachable at ${API}"
# Local brokers log every connection open and close at info level to stdout. Under the connection churn of
# queue:task:start (one connection per task, about 230 a minute) the stdout logger runs in sync mode and
# new connections wait on it: 15 of 60 handshakes took 5 to 10 s, and the core's 3 s connect timeout
# failed imports and sync:data (2026-11-04, b2b, RabbitMQ 4.2). At level warning 0 of 80 stalled.
# BROKER_LOG_LEVEL= (empty) leaves the broker as it is. Runtime only: a broker restart reverts it.
BROKER_LOG_LEVEL="${BROKER_LOG_LEVEL-warning}"
if [ -n "${BROKER_LOG_LEVEL}" ]; then
    docker exec "${BROKER_CONTAINER:-${CONTAINER%_cli_1}_broker_1}" rabbitmqctl -q set_log_level "${BROKER_LOG_LEVEL}" \
        || fail "could not set the broker log level to ${BROKER_LOG_LEVEL} (set BROKER_CONTAINER, or BROKER_LOG_LEVEL= to skip)"
    printf '  broker log level: %s\n' "${BROKER_LOG_LEVEL}"
fi
if [ "${SCHEDULER}" = "1" ]; then
    # Without jobs a resumed scheduler starts nothing and the drain wait runs into its 2 hour limit.
    SCHEDULER_JOBS=$(docker exec "${SCHEDULER_CONTAINER:-spryker_scheduler_1}" sh -c 'ls /var/jenkins_home/jobs 2>/dev/null' | grep -c . || true)
    [ "${SCHEDULER_JOBS:-0}" -gt 0 ] || fail "the scheduler has no jobs; run: docker/sdk console scheduler:setup"
    printf '  scheduler jobs: %s\n' "${SCHEDULER_JOBS}"
fi

# Contract C2: the sync mode is read from the application, never inferred from source. Direct sync
# writes storage from an in-process buffer and leaves the sync queues nearly empty, so a run in one
# mode is not comparable with a run in the other. Read-only: the probe only loads classes.
say "0b. Resolving the sync mode"
docker exec -w /data "${CONTAINER}" php tools/ps-metrics/sync-mode.php --control > "${OUT_DIR}/sync-mode.json" 2> "${OUT_DIR}/sync-mode.err" \
    || fail "sync mode probe failed, see ${OUT_DIR}/sync-mode.err"
python3 - "${OUT_DIR}/sync-mode.json" <<'SYNC' || fail "sync mode could not be resolved, see ${OUT_DIR}/sync-mode.json"
import json, sys
data = json.load(open(sys.argv[1]))
mode = data.get('sync_mode')
control = data.get('control', {})
print(f"  sync mode: {mode} (from {data.get('sync_mode_source')}; "
      f"{data['sync_mode_entities']['direct_tables']} direct, {data['sync_mode_entities']['queue_tables']} queue tables)")
# Positive control: the same checks must be able to answer "queue", or "direct" means nothing.
print(f"  control  : config without dynamic store -> {control.get('config_mode_without_dynamic_store')}, "
      f"classifier on a stub returning false -> {control.get('entity_classifier_on_stub_returning_false')}")
if data.get('sync_mode_stale'):
    print('  WARNING: config and generated entities disagree; the generated code is stale, its mode is what runs')
ok = mode in ('direct', 'queue') and control.get('config_mode_without_dynamic_store') == 'queue' \
    and control.get('entity_classifier_on_stub_returning_false') == 'queue'
sys.exit(0 if ok else 1)
SYNC

# A settings file left by an interrupted run would instrument the next workers.
docker exec "${CONTAINER}" rm -f "${SETTINGS_FILE_IN_CONTAINER}" >/dev/null 2>&1 || true
ERROR_BASELINE_FILE="${OUT_DIR}/error-queues-before.json"
if [ "${SCHEDULER}" = "1" ]; then
    say "1. Scheduler keeps running"
    # Idempotent: puts an environment a runner-mode run left suspended back into its regular state.
    docker/sdk console scheduler:resume >/dev/null 2>&1 || fail "scheduler:resume failed"
    say "2. Waiting until the scheduler has emptied the queues"
    left=""
    for _ in $(seq 1 120); do
        left=$(work_total)
        [ "${left}" = "0" ] && break
        sleep 5
    done
    [ "${left}" = "0" ] || fail "queues still hold ${left} message(s) after 10 minutes"
    # Error queues are not consumed and are not purged: the run reports what it added to them.
    "${CURL[@]}" "${API}/queues" | python3 -c 'import sys,json;print(json.dumps({q["name"]:q.get("messages",0) for q in json.load(sys.stdin) if q["name"].endswith(".error")}))' > "${ERROR_BASELINE_FILE}"
else
    # The scheduler launches queue workers on its own, without the metrics flag. They consume the
    # messages and write no records, which is exactly how the first run lost its data.
    say "1. Suspending the scheduler"
    docker/sdk console scheduler:suspend >/dev/null 2>&1 || echo "WARNING: scheduler:suspend failed, continuing"

    say "2. Killing stray workers"
    docker exec "${CONTAINER}" bash -lc "pkill -f 'queue:worker:start' || true; pkill -f 'queue:task:start' || true" >/dev/null 2>&1 || true

    # Unacked messages from a killed consumer take time to requeue; a new consumer sees nothing until
    # the broker has released them.
    say "3. Waiting for consumers to clear"
    for _ in $(seq 1 30); do
        [ "$(consumer_total)" = "0" ] && break
        sleep 2
    done
    [ "$(consumer_total)" = "0" ] || fail "consumers still attached, a worker is still alive"

    say "4. Purging queues"
    # One process for the whole purge: a shell loop spawning curl and python per queue is both slow
    # across ~180 queues and silently lossy, because each failure is swallowed individually.
    python3 - "${API}" "${BROKER_USER}" "${BROKER_PASS}" <<'PURGE'
import base64, json, sys, urllib.error, urllib.parse, urllib.request

api, user, password = sys.argv[1], sys.argv[2], sys.argv[3]
auth = base64.b64encode(f'{user}:{password}'.encode()).decode()


def call(url, method='GET'):
    request = urllib.request.Request(url, method=method)
    request.add_header('Authorization', f'Basic {auth}')
    return urllib.request.urlopen(request, timeout=30)


queues = json.load(call(f'{api}/queues'))
purged = failed = 0
for queue in queues:
    if not queue.get('messages'):
        continue
    vhost = urllib.parse.quote(queue['vhost'], safe='')
    name = urllib.parse.quote(queue['name'], safe='')
    try:
        call(f'{api}/queues/{vhost}/{name}/contents', method='DELETE')
        purged += 1
    except urllib.error.HTTPError as error:
        failed += 1
        print(f'  could not purge {queue["name"]}: HTTP {error.code}', file=sys.stderr)

print(f'  purged {purged} queue(s), {failed} failed')
PURGE
    # Polled rather than asserted once: a purge leaves messages settling for a moment, and calling
    # queue_total twice (once to test, once to report) raced badly enough to fail with "not empty: 0".
    purge_total=""
    for _ in $(seq 1 30); do
        purge_total=$(queue_total)
        [ "${purge_total}" = "0" ] && break
        sleep 2
    done
    [ "${purge_total}" = "0" ] || fail "queues not empty after purge: ${purge_total}"
    echo '{}' > "${ERROR_BASELINE_FILE}"
fi

# STORAGE_RESET=1 (runner mode default) empties key-value store and search before the run, so fill curves
# start at zero. The scheduler mode leaves them as they are (STORAGE_RESET=0), as in production.
STORAGE_RESET="${STORAGE_RESET:-$([ "${SCHEDULER}" = "1" ] && echo 0 || echo 1)}"
if [ "${STORAGE_RESET}" = "1" ]; then
say "5. Emptying the storages"
docker exec "${CONTAINER}" bash -lc "redis-cli -h key_value_store -t 30 FLUSHALL" >/dev/null 2>&1 || true
docker exec "${CONTAINER}" bash -lc "curl -s -X DELETE 'http://search:9200/spryker_*'" >/dev/null 2>&1 || true
# Deleting the indices also deletes their mappings. Without this the first document of a run creates
# each index by dynamic mapping, which guesses field types from that document: a scaled run whose first
# product has a numeric facet then rejects every later text facet (mapper cannot be changed from text
# to long), the messages land in the error queue and the run measures nothing.
docker/sdk cli "vendor/bin/console search:setup:sources" >/dev/null 2>&1 \
    || fail "search:setup:sources failed after deleting the indices"
# `docker/sdk cli` exits 0 even when the command failed, so the result is checked: the indices exist
# again, empty.
SEARCH_INDICES=$(docker exec "${CONTAINER}" bash -lc "curl -s 'http://search:9200/_cat/indices/spryker_*?h=index'" 2>/dev/null | grep -c . || true)
[ "${SEARCH_INDICES:-0}" -gt 0 ] || fail "no search index exists after search:setup:sources"
printf '  search indices recreated: %s\n' "${SEARCH_INDICES}"
else
    printf '\n=== 5. Storages ===\n  left as they are (STORAGE_RESET=0)\n'
fi

# Contract C8: the import path follows from what the database holds before the import, so it is read
# here, after the storages were emptied and before anything is imported. Read-only.
# RESET_DB=1 empties the database before the import, so the import inserts (first full load) instead
# of updating. DESTRUCTIVE for the local database; take a dump first (reset-db.sh dump).
if [ "${RESET_DB:-0}" = "1" ]; then
    say "5a. Resetting the database (insert path)"
    tools/ps-metrics/reset-db.sh reset || fail "database reset failed"
fi

say "5b. Source inventory before the import"
IMPORT_PATH=$(python3 tools/ps-metrics/source-inventory.py before --config "${IMPORT_CONFIG}" \
    --out "${OUT_DIR}/source-inventory.json" --container "${CONTAINER}") \
    || { echo "WARNING: source inventory before the import failed"; IMPORT_PATH=unknown; }
printf '  import path: %s\n' "${IMPORT_PATH}"

# A config that adds stores needs their queues, search indices and store storage before its catalog
# data is synchronized: on 2026-11-04 the s010 set created the FR, NL and CH indices by dynamic mapping
# and left their store storage without currency, and Yves and Glue answered 500 (see store-setup.sh).
# Runs outside the measured window; the store actions run again inside the import, as updates.
if [ "${IMPORT}" = "1" ]; then
    say "5c. Store setup before the import"
    CLI_CONTAINER="${CONTAINER}" tools/ps-metrics/store-setup.sh prepare \
        --import-config "${IMPORT_CONFIG}" --work-dir "${OUT_DIR}/store-setup" || fail "store setup failed"
fi

say "6. Starting the broker sampler"
RABBITMQ_HOST="${BROKER_HOST}" RABBITMQ_MANAGEMENT_PORT="${BROKER_PORT}" \
    nohup tools/ps-metrics/queue-sampler.sh \
        --out "${OUT_DIR}/queue-samples.jsonl" --interval 1 --duration "${MAX_WAIT_S}" \
        > "${OUT_DIR}/sampler.log" 2>&1 &
SAMPLER_PID=$!
sleep 3
kill -0 "${SAMPLER_PID}" 2>/dev/null || fail "sampler did not start"
STORAGE_SAMPLER_PID=""
SERVICE_SAMPLER_PID=""
stop_samplers() {
    kill "${SAMPLER_PID}" 2>/dev/null || true
    [ -n "${STORAGE_SAMPLER_PID}" ] && kill "${STORAGE_SAMPLER_PID}" 2>/dev/null || true
    [ -n "${SERVICE_SAMPLER_PID}" ] && kill "${SERVICE_SAMPLER_PID}" 2>/dev/null || true
}
trap stop_samplers EXIT

METRICS_IN_CONTAINER="/data/${OUT_DIR}/task-runs.jsonl"
IMPORT_METRICS_IN_CONTAINER="/data/${OUT_DIR}/import-runs.jsonl"
FLUSH_METRICS_IN_CONTAINER="/data/${OUT_DIR}/sync-flush-runs.jsonl"
if [ "${INSTRUMENTED}" = "1" ]; then
    ENV_FLAGS="QUEUE_METRICS_ENABLED=1 QUEUE_METRICS_OUTPUT_FILE=${METRICS_IN_CONTAINER}"
    # Contract C1: import records. Set explicitly rather than relying on the default location beside
    # the task-run file, so the path the runner collects from is the path the hooks write to.
    ENV_FLAGS="${ENV_FLAGS} QUEUE_METRICS_IMPORT_OUTPUT_FILE=${IMPORT_METRICS_IN_CONTAINER}"
    # Per-data-set row histograms are opt-in (high frequency); pass the switch through when set.
    if [ -n "${QUEUE_METRICS_IMPORT_ROWS:-}" ]; then
        ENV_FLAGS="${ENV_FLAGS} QUEUE_METRICS_IMPORT_ROWS=${QUEUE_METRICS_IMPORT_ROWS}"
        printf '  import row histograms: on\n'
    fi
    # Contract C3: time breakdown per task run and listener. On by default in a measurement run;
    # QUEUE_METRICS_BREAKDOWN=0 switches it off to compare the breakdown hooks' own cost.
    ENV_FLAGS="${ENV_FLAGS} QUEUE_METRICS_BREAKDOWN=${QUEUE_METRICS_BREAKDOWN:-1}"
    printf '  time breakdown: %s\n' "${QUEUE_METRICS_BREAKDOWN:-1}"
    # Contract C6: under direct sync the storage and search writes happen in a flush at console
    # terminate, outside every task run. Path set explicitly, like the import records.
    ENV_FLAGS="${ENV_FLAGS} QUEUE_METRICS_SYNC_FLUSH_OUTPUT_FILE=${FLUSH_METRICS_IN_CONTAINER}"
else
    # No metrics variable at all, not a 0: the instrumentation must see exactly what production sees.
    ENV_FLAGS=""
    printf '  instrumentation: OFF (overhead baseline, no task, listener or import records)\n'
fi

# Chunk size is the single most valuable lever in the system, so a run can set it. The core default
# of 10000 for storage sync can exceed the broker consumer timeout on a large queue, which surfaces
# as "Consumer timeout exceed" and fails the stage.
if [ -n "${QUEUE_MESSAGE_CHUNK_SIZE_MAP:-}" ]; then
    # Single-quoted on purpose: the value is JSON, and its double quotes do not survive the trip
    # through `docker/sdk cli "<flags> <command>"` unquoted. json_decode then returns null, the map
    # is silently empty, and the run uses the default chunk while reporting that a map was set.
    ENV_FLAGS="${ENV_FLAGS} QUEUE_MESSAGE_CHUNK_SIZE_MAP='${QUEUE_MESSAGE_CHUNK_SIZE_MAP}'"
    printf '  chunk size map: %s\n' "${QUEUE_MESSAGE_CHUNK_SIZE_MAP}"
fi

stage() {
    local name="$1"; shift
    local started ended
    started=$(date +%s)
    say "${name}"
    # Full output is kept: piping a stage through `tail -3` once discarded the only copy of an
    # import failure. `docker/sdk cli` also exits 0 when the command inside the container failed,
    # so the exit code alone is not a reliable signal and the log is inspected too.
    docker/sdk cli "${ENV_FLAGS} $*" > "${OUT_DIR}/stage-${name}.log" 2>&1 || true
    if grep -qiE '\[critical\]|Fatal error|Uncaught|^SPLIT_FAILED' "${OUT_DIR}/stage-${name}.log"; then
        tail -5 "${OUT_DIR}/stage-${name}.log"
        fail "stage ${name} reported a critical error, full log: ${OUT_DIR}/stage-${name}.log"
    fi
    tail -2 "${OUT_DIR}/stage-${name}.log"
    ended=$(date +%s)
    printf '%s,%s,%s,%s\n' "${name}" "${started}" "${ended}" "$((ended - started))" >> "${OUT_DIR}/stages.csv"
    printf '  %s took %ss\n' "${name}" "$((ended - started))"
}

echo "stage,started,ended,seconds" > "${OUT_DIR}/stages.csv"

# Contract C5: storage fill, 1 Hz, from import start to drain end. Started here rather than with the
# broker sampler so the curve starts at the empty store the import fills, not at the flush.
nohup python3 tools/ps-metrics/storage-sampler.py \
    --out "${OUT_DIR}/storage-samples.jsonl" --container "${CONTAINER}" --interval 1 --duration "${MAX_WAIT_S}" \
    > "${OUT_DIR}/storage-sampler.log" 2>&1 &
STORAGE_SAMPLER_PID=$!
# Contract C8: CPU and memory of the database, key-value, search, broker and cli containers, 1 Hz, over
# the same window. One process holding one streaming `docker stats` child.
nohup python3 tools/ps-metrics/service-sampler.py \
    --out "${OUT_DIR}/service-samples.jsonl" --interval 1 --duration "${MAX_WAIT_S}" \
    > "${OUT_DIR}/service-sampler.log" 2>&1 &
SERVICE_SAMPLER_PID=$!

if [ "${SCHEDULER}" = "1" ]; then
    say "6b. Metrics settings for the scheduler's workers"
    if [ "${INSTRUMENTED}" = "1" ]; then
        python3 - "${ENV_FLAGS}" "${SETTINGS_TTL}" <<'SETTINGS' | docker exec -i "${CONTAINER}" sh -c "cat > '${SETTINGS_FILE_IN_CONTAINER}'"
import json, shlex, sys, time
env = dict(pair.split('=', 1) for pair in shlex.split(sys.argv[1]) if pair.startswith('QUEUE_METRICS_'))
print(json.dumps({'expires_at': time.time() + int(sys.argv[2]), 'env': env}))
SETTINGS
        docker exec "${CONTAINER}" test -s "${SETTINGS_FILE_IN_CONTAINER}" || fail "settings file was not written"
        # The file is read by the module only when the path matches its default, so the check runs there.
        SETTINGS_SEEN=$(docker exec -w /data "${CONTAINER}" php -r 'echo sys_get_temp_dir();')
        [ "${SETTINGS_SEEN}/queue-metrics.settings.json" = "${SETTINGS_FILE_IN_CONTAINER}" ] \
            || fail "settings file ${SETTINGS_FILE_IN_CONTAINER} is not the module default under ${SETTINGS_SEEN}"
        printf '  settings file: %s (valid %ss)\n' "${SETTINGS_FILE_IN_CONTAINER}" "${SETTINGS_TTL}"
    fi
fi

IMPORT_PHP="php${IMPORT_MEMORY_LIMIT:+ -d memory_limit=${IMPORT_MEMORY_LIMIT}} vendor/bin/console"
if [ "${IMPORT}" = "1" ] && [ "${IMPORT_SPLIT}" = "1" ]; then
    # One config per action and a loop script, written to the bundle and run in the container. The
    # metrics variables prefix `bash`, so every import process inherits them.
    split_dir="${OUT_DIR}/import-split"
    mkdir -p "${split_dir}"
    python3 - "${IMPORT_CONFIG}" "${split_dir}" <<'PY'
import pathlib, re, sys
config, out = pathlib.Path(sys.argv[1]), pathlib.Path(sys.argv[2])
actions = re.findall(r'-\s*data_entity:\s*(\S+)\s*\n\s*source:\s*(\S+)', config.read_text())
names = []
for i, (entity, source) in enumerate(actions):
    name = f'{i:03d}-{entity}.yml'
    (out / name).write_text(f'version: 0\nactions:\n  - data_entity: {entity}\n    source: {source}\n')
    names.append(name)
(out / 'actions.txt').write_text(''.join(n + '\n' for n in names))
PY
    # Stops at the first failed process. Running on after a failure (the product-stock process killed
    # out of memory in the 10% ICP run) costs hours and leaves the database without that entity. Each
    # exit code goes to the log, which the stage inspects; the container's files do not sync back.
    # A failed action is retried IMPORT_RETRIES times (default 1), each retry logged as SPLIT_RETRY. The
    # 1% ICP rerun lost product-approval-status to an AMQP connect timeout (3 s, core default) at its
    # first event send, with the cli container at 5 cores from the scheduler's workers. Importers write
    # by upsert and add their publish events for every row, so a rerun of the action is safe.
    printf 'set -u\nwhile read -r f; do n=0; while :; do %s data:import --config=/data/%s/"$f"; rc=$?; echo "SPLIT_EXIT $f $rc"; [ "$rc" = 0 ] && break; n=$((n+1)); [ "$n" -gt %s ] && { echo "SPLIT_FAILED $f exit $rc"; exit 1; }; echo "SPLIT_RETRY $f attempt $n after exit $rc"; done; done < /data/%s/actions.txt\n' \
        "${IMPORT_PHP}" "${split_dir}" "${IMPORT_RETRIES}" "${split_dir}" > "${split_dir}/run.sh"
    # The bundle is written on the host; wait until the container sees the script (file sync on macOS).
    for _ in $(seq 1 60); do docker exec "${CONTAINER}" test -f "/data/${split_dir}/run.sh" && break; sleep 1; done
    docker exec "${CONTAINER}" test -f "/data/${split_dir}/run.sh" || fail "split import script not visible in the container"
    printf '  import split into %s processes\n' "$(wc -l < "${split_dir}/actions.txt" | tr -d ' ')"
    stage "import"  "bash /data/${split_dir}/run.sh"
elif [ "${IMPORT}" = "1" ]; then
    stage "import"  "${IMPORT_PHP} data:import --config=${IMPORT_CONFIG}"
else
    printf '\n=== import ===\n  skipped (IMPORT=0): P&S only, on the data already in the database\n'
fi
if [ "${PUBLISH_TRIGGER}" = "1" ]; then
    stage "publish" "vendor/bin/console publish:trigger-events${PUBLISH_RESOURCES:+ -r ${PUBLISH_RESOURCES}}"
else
    printf '\n=== publish ===\n  skipped (PUBLISH_TRIGGER=0): only the events the import emitted are processed\n'
fi
if [ "${SYNC_DATA}" = "1" ]; then
    stage "sync"    "vendor/bin/console sync:data"
else
    printf '\n=== sync ===\n  skipped (SYNC_DATA=0): only what the import events produce is synchronized\n'
fi
if [ "${SCHEDULER}" = "1" ]; then
    # Drain end: no message in a consumed queue and no task process, three samples in a row, so a gap
    # between two scheduler worker starts is not taken for the end.
    say "drain"
    drain_started=$(date +%s)
    # Wall clock, not loop passes: one pass (broker API, docker exec, sleep) takes about 1.5 s, so a pass
    # count of MAX_WAIT_S ran about 36 h for MAX_WAIT_S=86400.
    drain_deadline=$((drain_started + MAX_WAIT_S))
    quiet=0
    next_livelock_check=$((drain_started + 60))
    while [ "$(date +%s)" -lt "${drain_deadline}" ]; do
        t=$(work_total)
        tasks=$(docker exec "${CONTAINER}" bash -lc "pgrep -fc 'queue:task:[s]tart' || true" 2>/dev/null | tr -d ' \r')
        if [ "$t" = "0" ] && [ "${tasks:-0}" = "0" ]; then quiet=$((quiet + 1)); else quiet=0; fi
        [ "${quiet}" -ge 3 ] && break
        if [ "$(date +%s)" -ge "${next_livelock_check}" ]; then
            next_livelock_check=$(( $(date +%s) + 60 ))
            livelock_check && break
        fi
        sleep 1
    done
    drain_ended=$(( $(date +%s) - 2 ))
    if [ "${LIVELOCK}" = "1" ]; then
        # Kept as its own stage name: a drain that never ends has no drain time.
        printf 'drain_livelock,%s,%s,%s\n' "${drain_started}" "${drain_ended}" "$((drain_ended - drain_started))" >> "${OUT_DIR}/stages.csv"
        printf '  LIVELOCK after %ss: %s\n' "$((drain_ended - drain_started))" "$(cat "${OUT_DIR}/livelock.json")"
    else
        [ "${quiet}" -ge 3 ] || fail "queues did not drain under the scheduler within ${MAX_WAIT_S} s"
        printf 'drain,%s,%s,%s\n' "${drain_started}" "${drain_ended}" "$((drain_ended - drain_started))" >> "${OUT_DIR}/stages.csv"
        printf '  drain took %ss after the last stage\n' "$((drain_ended - drain_started))"
    fi
    docker exec "${CONTAINER}" rm -f "${SETTINGS_FILE_IN_CONTAINER}" >/dev/null 2>&1 || true
else
    stage "drain"   "vendor/bin/console queue:worker:start --stop-when-empty"
fi

# The worker returns before its children finish, so wait for the queues to settle rather than
# trusting the command's exit.
say "7. Waiting for the queues to drain"
# After a livelock the queues stay full; waiting here would only add an hour.
[ "${LIVELOCK}" = "1" ] && printf '  skipped: livelock\n'
for _ in $( [ "${LIVELOCK}" = "1" ] || seq 1 240); do
    t=$(work_total)
    printf '  queued: %s\n' "$t"
    [ "$t" = "0" ] && break
    sleep 15
done
ERROR_QUEUES_AND_COUNT=$("${CURL[@]}" "${API}/queues" | python3 -c '
import sys, json
before = json.load(open(sys.argv[1]))
added = {q["name"]: q.get("messages", 0) - before.get(q["name"], 0) for q in json.load(sys.stdin) if q["name"].endswith(".error")}
added = {k: v for k, v in added.items() if v > 0}
print(sum(added.values()), ",".join(f"{k}={v}" for k, v in added.items()))' "${ERROR_BASELINE_FILE}")
ERROR_MESSAGES="${ERROR_QUEUES_AND_COUNT%% *}"
printf '  in error queues: %s\n' "${ERROR_MESSAGES}"
# Messages in error queues are a result, not a reason to discard the run: the first-load path showed a
# reproducible duplicate-key failure. The run is kept, the count and the error queues go into meta.json,
# and run-campaign.sh reports the run as "errors". Set ERRORS_FAIL=1 to fail the run instead.
ERROR_QUEUES=$(printf '%s' "${ERROR_QUEUES_AND_COUNT}" | sed 's/^[0-9]* *//')
if [ "${ERROR_MESSAGES}" != "0" ]; then
    printf '  WARNING: %s message(s) in error queues: %s\n' "${ERROR_MESSAGES}" "${ERROR_QUEUES}"
    [ "${ERRORS_FAIL:-0}" = "1" ] && fail "${ERROR_MESSAGES} message(s) ended in error queues"
fi

stop_samplers
trap - EXIT

# Contract C8: storage size at drain end, read-only.
say "7b. Storage size at drain end"
tools/ps-metrics/storage-size.sh --out "${OUT_DIR}/storage-size.json" --container "${CONTAINER}" \
    || echo "WARNING: storage size could not be read"

say "8. Positive control"
# Never DBSIZE: it returns 0 on KVrocks even when the store is full.
# Counted from INFO keyspace across every database rather than a bare --scan. Two traps here, both
# hit for real: DBSIZE returns 0 on KVrocks, and `--scan` without -n only ever sees db0 while the
# application writes to db1, which reported an empty store that held 17113 keys.
# kv-keys.sh is engine-aware: on Kvrocks INFO keyspace is stale until a DBSIZE SCAN finished.
KV_KEYS=$(tools/ps-metrics/kv-keys.sh --settle --container "${CONTAINER}" | tr -d ' \r')
ES_DOCS=$(docker exec "${CONTAINER}" bash -lc "curl -s 'http://search:9200/_cat/indices?h=docs.count' 2>/dev/null | awk '{s+=\$1} END {print s+0}'" | tr -d ' \r')
printf '  key-value keys      : %s\n' "${KV_KEYS}"
printf '  elasticsearch docs  : %s\n' "${ES_DOCS}"
[ "${KV_KEYS}" -gt 0 ] 2>/dev/null || fail "key-value store is empty, the run produced nothing"
[ "${ES_DOCS}" -gt 0 ] 2>/dev/null || fail "elasticsearch is empty, the run produced nothing"
# Non-empty storages do not mean a usable shop: every store needs its currency and country in store
# storage and search indices with their real mappings.
CLI_CONTAINER="${CONTAINER}" tools/ps-metrics/store-setup.sh check || fail "a store cannot serve Yves and Glue, see above"

# Contract C8: sources of the config used, and catalog row counts after the import. Catalog tables are
# not written by publish and sync, so counts taken here equal the counts right after the import, and
# taking them here keeps the query out of the measured window.
say "8b. Source inventory after the import"
python3 tools/ps-metrics/source-inventory.py after --config "${IMPORT_CONFIG}" \
    --out "${OUT_DIR}/source-inventory.json" --container "${CONTAINER}" \
    || echo "WARNING: source inventory after the import failed"
# Proof that every importer succeeded and no row was skipped; a scaled subset must pass this.
if [ "${IMPORT}" = "1" ]; then
    python3 tools/ps-metrics/scale-import.py check-log "${OUT_DIR}/stage-import.log" --config "${IMPORT_CONFIG}" --json \
        > "${OUT_DIR}/import-check.json" || echo "WARNING: import check failed, see ${OUT_DIR}/import-check.json"
fi

say "9. Collecting"
if [ "${INSTRUMENTED}" = "1" ]; then
    # docker/sdk on macOS syncs host to container but not reliably the other way.
    docker cp "${CONTAINER}:${METRICS_IN_CONTAINER}" "${OUT_DIR}/task-runs.jsonl" 2>/dev/null \
        || echo "WARNING: no task-runs.jsonl produced"

    # The listener records are written by the OTEL hook at process shutdown, beside the task runs.
    docker cp "${CONTAINER}:$(dirname "${METRICS_IN_CONTAINER}")/listener-runs.jsonl" \
        "${OUT_DIR}/listener-runs.jsonl" 2>/dev/null \
        || echo "NOTE: no listener-runs.jsonl produced (no publish queues drained in this run)"

    # Import records (C1) are written by the data:import hooks as each span ends.
    docker cp "${CONTAINER}:${IMPORT_METRICS_IN_CONTAINER}" "${OUT_DIR}/import-runs.jsonl" 2>/dev/null \
        || echo "NOTE: no import-runs.jsonl produced (import hooks absent or disabled)"

    # Direct-sync flush records (C6), one per flush at console terminate.
    docker cp "${CONTAINER}:${FLUSH_METRICS_IN_CONTAINER}" "${OUT_DIR}/sync-flush-runs.jsonl" 2>/dev/null \
        || echo "NOTE: no sync-flush-runs.jsonl produced (queue sync mode, or flush hooks absent)"
else
    echo "  uninstrumented run: no metrics records to collect"
    # Evidence that the switch was really off: the hooks would have created the task-run file.
    if docker exec "${CONTAINER}" test -e "${METRICS_IN_CONTAINER}" 2>/dev/null; then
        fail "uninstrumented run produced ${METRICS_IN_CONTAINER}; QUEUE_METRICS_ENABLED is set elsewhere"
    fi
fi

python3 - "$OUT_DIR" "$RUN_ID" "$KV_KEYS" "$ES_DOCS" "$INSTRUMENTED" "$SCALE" "$IMPORT_CONFIG" "$IMPORT_PATH" "$PUBLISH_TRIGGER" "$SYNC_DATA" "$ERROR_MESSAGES" "$ERROR_QUEUES" "$SCHEDULER" "$IMPORT" "$PUBLISH_RESOURCES" "$STORAGE_RESET" <<'PY'
import json, os, subprocess, sys, pathlib
out, run_id, kv, es, instrumented = sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4], sys.argv[5] == '1'
scale, import_config, import_path, publish_trigger = float(sys.argv[6]), sys.argv[7], sys.argv[8], sys.argv[9] == '1'
sha = subprocess.run(['git','rev-parse','HEAD'], capture_output=True, text=True).stdout.strip()
branch = subprocess.run(['git','rev-parse','--abbrev-ref','HEAD'], capture_output=True, text=True).stdout.strip()
sync = json.loads(pathlib.Path(out, 'sync-mode.json').read_text())
meta = {
    'run_id': run_id, 'git_sha': sha, 'branch': branch,
    'kv_keys': int(kv), 'es_docs': int(es),
    'scheduler': 'running, never paused' if sys.argv[13] == '1' else 'suspended',
    'storages': 'emptied before run' if sys.argv[16] == '1' else 'left as they were',
    'worker_source': 'scheduler' if sys.argv[13] == '1' else 'queue:worker:start --stop-when-empty',
    # C2. The full probe output, positive control included, stays in sync-mode.json.
    'sync_mode': sync['sync_mode'],
    'sync_mode_source': sync['sync_mode_source'],
    'sync_mode_stale': sync['sync_mode_stale'],
    # A-20: false for the overhead baseline, which ran without QUEUE_METRICS_ENABLED.
    'instrumented': instrumented,
    # C8. scale is the requested fraction; the subset manifest gives the fraction actually built.
    'scale': scale,
    'import_config': import_config,
    'import_ran': sys.argv[14] == '1',
    'publish_resources': sys.argv[15] or None,
    'storage_reset': sys.argv[16] == '1',
    'import_path': import_path if import_path in ('update', 'insert') else None,
    'publish_trigger': publish_trigger,
    'sync_data': sys.argv[10] == '1',
    'error_messages': int(sys.argv[11] or 0),
    'error_queues': sys.argv[12] or None,
    'broker_log_level': os.environ.get('BROKER_LOG_LEVEL', 'warning') or 'unchanged',
}
manifest = pathlib.Path(import_config).parent / 'manifest.json'
# Two kinds of manifest sit next to an import config: a scale-import.py subset, and a csv-generator.php
# set (its counts under 'counts'). Reading the second as the first failed the 10% ICP run on a KeyError.
m = json.loads(manifest.read_text()) if manifest.exists() else {}
if all(k in m for k in ('abstracts', 'concretes', 'offers', 'product_rows')):
    meta['scale_subset'] = {'manifest': str(manifest), 'abstracts': m['abstracts'], 'concretes': m['concretes'],
                            'offers': m['offers'], 'product_rows': m['product_rows']}
elif isinstance(m.get('counts'), dict):
    meta['generated_set'] = {'manifest': str(manifest), 'scale': m.get('scale'), 'index_offset': m.get('index_offset'),
                             'counts': m['counts']}
livelock = pathlib.Path(out, 'livelock.json')
if livelock.exists() and livelock.stat().st_size > 0:
    meta['drain_livelock'] = json.loads(livelock.read_text())
for key, name in (('source_inventory_file', 'source-inventory.json'),
                  ('service_samples_file', 'service-samples.jsonl'),
                  ('storage_size_file', 'storage-size.json')):
    path = pathlib.Path(out, name)
    if path.exists() and path.stat().st_size > 0:
        meta[key] = name
check = pathlib.Path(out, 'import-check.json')
if check.exists() and check.stat().st_size > 0:
    meta['import_check'] = json.loads(check.read_text())
samples = pathlib.Path(out, 'storage-samples.jsonl')
if samples.exists() and samples.stat().st_size > 0:
    meta['storage_samples_file'] = samples.name
flushes = pathlib.Path(out, 'sync-flush-runs.jsonl')
if flushes.exists():
    meta['sync_flush_runs_file'] = flushes.name
imports = pathlib.Path(out, 'import-runs.jsonl')
if imports.exists() and imports.stat().st_size > 0:
    meta['import_runs_file'] = imports.name
pathlib.Path(out, 'meta.json').write_text(json.dumps(meta, indent=2) + '\n')
PY

say "Done"
printf 'Bundle: %s\n' "${OUT_DIR}"
ls -la "${OUT_DIR}"
# The bundle is complete; a livelocked drain still fails the run, so the campaign reports it.
[ "${LIVELOCK}" = "1" ] && { printf '\nRUN FAILED: drain livelock, see livelock.json\n' >&2; exit 3; }
exit 0
