#!/usr/bin/env bash
#
# SOL-629 measured import + Publish & Synchronize run on a Spryker PaaS environment.
#
# Runs INSIDE the Jenkins container (a one-off Jenkins job), from /data, with the region or store env
# file sourced. The cloud counterpart of run-baseline.sh in its SCHEDULER=1 mode: the scheduler is never
# paused, its regular worker jobs consume while the import runs, and the metrics settings reach those
# workers through the settings file (SettingsFile), because they are separate processes in this container.
#
# Every file is written to the container's local file system under OUT_ROOT/<RUN_ID>. At the end the
# bundle is packed into BUNDLE_TGZ (default: the Jenkins workspace), so the job can archive it and it can
# be fetched with the Jenkins API. The bundle layout is the one render-report.py reads.
#
# Usage (Jenkins shell step):
#   . /envs/<region-or-store>.env; cd /data
#   DATA_SET=gen-icp-v2-s001 DATA_URL='<presigned S3 URL of the .tgz>' RUN_ID=s001-1 bash data/ps-metrics/run-cloud.sh
#
# Environment:
#   DATA_SET          directory name under data/import, holding import.yml (required)
#   DATA_URL          URL of a .tgz holding DATA_SET/ (optional when the directory already exists)
#   RUN_ID            bundle name (default: <DATA_SET>-<UTC stamp>)
#   OUT_ROOT          default /data/data/metrics
#   BUNDLE_TGZ        default ${WORKSPACE:-/tmp}/ps-metrics-<RUN_ID>.tgz
#   INSTRUMENTED      1 (default) or 0: same stages without metrics, the overhead baseline
#   BREAKDOWN         1 (default): per-call breakdown hooks, as the local instrumented runs
#   IMPORT_SPLIT      1 (default): one data:import process per action, a big catalog does not fit one
#   IMPORT_MEMORY_LIMIT  php memory_limit for import processes, default -1
#   STORE_SETUP       1 (default): create the stores of the set before the measured window
#   MAX_WAIT_S        drain limit, default 14400
#   STALL_S           fail when work is queued and no ack happens for this long, default 1800
#   SAMPLE_S          sampler interval, default 2
#   WORK_QUEUE_EXCLUDE  comma list of queues not counted as P&S work, default scheduler-jobs
#   GIT_BRANCH_NAME, GIT_SHA  recorded in meta.json (the image has no .git)

set -uo pipefail

DATA_SET="${DATA_SET:?DATA_SET is required}"
RUN_ID="${RUN_ID:-${DATA_SET}-$(date -u +%Y%m%d-%H%M%S)}"
OUT_ROOT="${OUT_ROOT:-/data/data/metrics}"
OUT="${OUT_ROOT}/${RUN_ID}"
BUNDLE_TGZ="${BUNDLE_TGZ:-${WORKSPACE:-/tmp}/ps-metrics-${RUN_ID}.tgz}"
INSTRUMENTED="${INSTRUMENTED:-1}"
BREAKDOWN="${BREAKDOWN:-1}"
IMPORT_SPLIT="${IMPORT_SPLIT:-1}"
IMPORT_MEMORY_LIMIT="${IMPORT_MEMORY_LIMIT:--1}"
STORE_SETUP="${STORE_SETUP:-1}"
MAX_WAIT_S="${MAX_WAIT_S:-14400}"
STALL_S="${STALL_S:-1800}"
SAMPLE_S="${SAMPLE_S:-2}"
WORK_QUEUE_EXCLUDE="${WORK_QUEUE_EXCLUDE:-scheduler-jobs}"
SETTINGS_FILE="$(php -r 'echo sys_get_temp_dir();')/queue-metrics.settings.json"
IMPORT_DIR="/data/data/import/${DATA_SET}"
CONFIG="${IMPORT_DIR}/import.yml"
API="http://${SPRYKER_BROKER_API_HOST}:${SPRYKER_BROKER_API_PORT:-15672}/api"
API_AUTH="${SPRYKER_BROKER_API_USERNAME}:${SPRYKER_BROKER_API_PASSWORD}"
SEARCH="${SPRYKER_SEARCH_PROTOCOL:-http}://${SPRYKER_SEARCH_HOST}:${SPRYKER_SEARCH_PORT:-80}"
SEARCH_PREFIX="${SPRYKER_SEARCH_INDEX_PREFIX:-spryker}"

say()  { printf '\n=== %s  %s ===\n' "$(date -u +%H:%M:%S)" "$1"; }
fail() { printf '\nRUN FAILED: %s\n' "$1" >&2; finish 1; }

mkdir -p "${OUT}"
STAGES="${OUT}/stages.jsonl"
: > "${STAGES}"

# --- helpers ------------------------------------------------------------------------------------

queues_json() { curl -sf -u "${API_AUTH}" "${API}/queues?columns=name,vhost,messages,consumers,message_stats.ack_details,message_stats.ack" ; }

# Work = messages in queues a worker consumes: no error queues, no excluded queues.
work_total() {
    queues_json | python3 -c '
import json,sys
ex=set(sys.argv[1].split(","))
q=json.load(sys.stdin)
print(sum(x.get("messages",0) for x in q if not x["name"].endswith(".error") and x["name"] not in ex))' "${WORK_QUEUE_EXCLUDE}"
}
ack_total()   { queues_json | python3 -c 'import json,sys;print(sum((x.get("message_stats") or {}).get("ack",0) for x in json.load(sys.stdin)))'; }
error_total() { queues_json | python3 -c 'import json,sys;print(sum(x.get("messages",0) for x in json.load(sys.stdin) if x["name"].endswith(".error")))'; }
consumer_total() { queues_json | python3 -c 'import json,sys;print(sum(x.get("consumers",0) for x in json.load(sys.stdin)))'; }

# Key count over every key-value DB, from INFO keyspace (no redis-cli in the image).
kv_keys() {
    python3 - "${SPRYKER_KEY_VALUE_STORE_HOST}" "${SPRYKER_KEY_VALUE_STORE_PORT:-6379}" <<'PY'
import socket, sys, re
s = socket.create_connection((sys.argv[1], int(sys.argv[2])), timeout=10)
s.sendall(b"*2\r\n$4\r\nINFO\r\n$8\r\nkeyspace\r\n")
buf = b""
while True:
    chunk = s.recv(65536)
    if not chunk: break
    buf += chunk
    m = re.match(rb"\$(\d+)\r\n", buf)
    if m and len(buf) >= m.end() + int(m.group(1)): break
print(sum(int(k) for k in re.findall(rb"db\d+:keys=(\d+)", buf)))
PY
}
search_docs() { curl -sf "${SEARCH}/${SEARCH_PREFIX}*/_count" | python3 -c 'import json,sys;print(json.load(sys.stdin)["count"])'; }

stage_start() { STAGE_NAME="$1"; STAGE_T0=$(date +%s.%N); say "stage ${STAGE_NAME}"; }
stage_end() {
    local rc="$1" t1; t1=$(date +%s.%N)
    printf '{"stage":"%s","start":%s,"end":%s,"seconds":%.3f,"exit_code":%s}\n' \
        "${STAGE_NAME}" "${STAGE_T0}" "${t1}" "$(awk "BEGIN{print ${t1} - ${STAGE_T0}}")" "${rc}" >> "${STAGES}"
}

# Wait until the P&S work queues are empty. Fails on a stall: work queued and no ack for STALL_S.
drain() {
    local limit="$1" t0 last_ack last_move w a
    t0=$(date +%s); last_ack=$(ack_total); last_move=$t0
    while :; do
        w=$(work_total) || w=""
        [ -n "$w" ] || { sleep 5; continue; }
        [ "$w" -eq 0 ] && { sleep 10; w=$(work_total); [ "$w" -eq 0 ] && return 0; }
        a=$(ack_total)
        [ "$a" != "$last_ack" ] && { last_ack=$a; last_move=$(date +%s); }
        [ $(( $(date +%s) - last_move )) -gt "${STALL_S}" ] && { echo "stall: ${w} messages, no ack for ${STALL_S}s"; return 2; }
        [ $(( $(date +%s) - t0 )) -gt "${limit}" ] && { echo "drain limit ${limit}s reached, ${w} messages left"; return 1; }
        printf '  %s work=%s acks=%s\n' "$(date -u +%H:%M:%S)" "$w" "$a"
        sleep 30
    done
}

SAMPLER_PIDS=""
stop_samplers() { for p in ${SAMPLER_PIDS}; do kill "$p" 2>/dev/null; done; wait 2>/dev/null; SAMPLER_PIDS=""; }

finish() {
    local rc="$1"
    stop_samplers
    rm -f "${SETTINGS_FILE}"
    python3 - "${OUT}/meta.json" "${rc}" <<'PY'
import json, sys, os
p, rc = sys.argv[1], int(sys.argv[2])
m = json.load(open(p)) if os.path.exists(p) else {}
m["status"] = "ok" if rc == 0 else "failed"
m["exit_code"] = rc
json.dump(m, open(p, "w"), indent=1)
PY
    tar -czf "${BUNDLE_TGZ}" -C "${OUT_ROOT}" "${RUN_ID}" && echo "bundle: ${BUNDLE_TGZ} ($(du -h "${BUNDLE_TGZ}" | cut -f1))"
    exit "${rc}"
}
trap 'fail "interrupted"' INT TERM

# --- 1. preconditions ---------------------------------------------------------------------------

say "preconditions"
[ -d /data/vendor ] || fail "not in the application container"
curl -sf -u "${API_AUTH}" "${API}/overview" >/dev/null || fail "broker management API ${API} not reachable"
search_docs >/dev/null || fail "search ${SEARCH} not reachable"
kv_keys >/dev/null || fail "key-value store not reachable"
php -m | grep -qi opentelemetry || fail "opentelemetry extension missing, no hook can register"
[ "$(consumer_total)" -gt 0 ] || fail "no queue has a consumer: the scheduler workers are not running"
php data/ps-metrics/sync-mode.php --control > "${OUT}/sync-mode.json" 2> "${OUT}/sync-mode.err" \
    || fail "sync-mode probe failed: $(tail -3 "${OUT}/sync-mode.err")"
SYNC_MODE=$(python3 -c 'import json,sys;print(json.load(open(sys.argv[1])).get("sync_mode",""))' "${OUT}/sync-mode.json")
rm -f "${SETTINGS_FILE}"   # a file left by an interrupted run would instrument the next workers

# --- 2. data --------------------------------------------------------------------------------------

if [ ! -f "${CONFIG}" ]; then
    [ -n "${DATA_URL:-}" ] || fail "${CONFIG} missing and no DATA_URL"
    stage_start download
    curl -sf "${DATA_URL}" -o "/tmp/${DATA_SET}.tgz" && tar -xzf "/tmp/${DATA_SET}.tgz" -C /data/data/import && rm -f "/tmp/${DATA_SET}.tgz"
    rc=$?; stage_end $rc; [ $rc -eq 0 ] || fail "download or unpack of ${DATA_SET} failed"
fi
[ -f "${CONFIG}" ] || fail "${CONFIG} missing after unpack"
cp "${CONFIG}" "${OUT}/import.yml"; [ -f "${IMPORT_DIR}/manifest.json" ] && cp "${IMPORT_DIR}/manifest.json" "${OUT}/"

# --- 3. store setup, outside the measured window --------------------------------------------------

STORE_ENTITIES="store locale-store default-locale-store currency-store country-store context-store"
python3 - "${CONFIG}" "${OUT}" "${STORE_ENTITIES}" <<'PY'
import pathlib, re, sys
config, out, wanted = pathlib.Path(sys.argv[1]), pathlib.Path(sys.argv[2]), set(sys.argv[3].split())
actions = re.findall(r'-\s*data_entity:\s*(\S+)\s*\n\s*source:\s*(\S+)', config.read_text())
if not actions:
    sys.exit("no actions found in " + str(config))
store = [(e, s) for e, s in actions if e in wanted]
rest = [(e, s) for e, s in actions if e not in wanted]
def write(path, items):
    path.write_text('version: 0\nactions:\n' + ''.join(f'  - data_entity: {e}\n    source: {s}\n' for e, s in items))
(out / 'actions').mkdir(exist_ok=True)
if store:
    write(out / 'store-setup.yml', store)
for i, (e, s) in enumerate(rest, 1):
    write(out / 'actions' / f'{i:03d}-{e}.yml', [(e, s)])
write(out / 'measured-import.yml', rest)
print(f'{len(store)} store actions, {len(rest)} measured actions')
PY
[ $? -eq 0 ] || fail "could not split ${CONFIG}"

if [ "${STORE_SETUP}" = 1 ]; then
    stage_start store-setup
    {
        if [ -f "${OUT}/store-setup.yml" ]; then vendor/bin/console data:import --config="${OUT}/store-setup.yml" || exit 1; fi
        vendor/bin/console queue:setup && vendor/bin/console search:setup:sources && vendor/bin/console publish:trigger-events -r store
    } > "${OUT}/stage-store-setup.log" 2>&1
    rc=$?; stage_end $rc; [ $rc -eq 0 ] || fail "store setup failed: $(tail -5 "${OUT}/stage-store-setup.log")"
fi

say "drain before the measured window"
drain 3600 || fail "queues did not drain before the run"

# --- 4. measured window ---------------------------------------------------------------------------

KV0=$(kv_keys); ES0=$(search_docs); ERR0=$(error_total)
ENV_FLAGS=""
if [ "${INSTRUMENTED}" = 1 ]; then
    ENV_FLAGS="QUEUE_METRICS_ENABLED=1 QUEUE_METRICS_OUTPUT_FILE=${OUT}/task-runs.jsonl QUEUE_METRICS_IMPORT_OUTPUT_FILE=${OUT}/import-runs.jsonl QUEUE_METRICS_SYNC_FLUSH_OUTPUT_FILE=${OUT}/sync-flush-runs.jsonl"
    [ "${BREAKDOWN}" = 1 ] && ENV_FLAGS="${ENV_FLAGS} QUEUE_METRICS_BREAKDOWN=1"
    # Workers are started by the scheduler, not by this script: they read the flags from the settings file.
    python3 - "${SETTINGS_FILE}" "$(( MAX_WAIT_S * 2 + 3600 ))" ${ENV_FLAGS} <<'PY'
import json, sys, time
path, ttl, pairs = sys.argv[1], int(sys.argv[2]), sys.argv[3:]
json.dump({"expires_at": time.time() + ttl, "env": dict(p.split("=", 1) for p in pairs)}, open(path, "w"))
PY
    [ -s "${SETTINGS_FILE}" ] || fail "settings file was not written"
fi

say "samplers"
OUT_FILE="${OUT}/queue-samples.jsonl" RABBITMQ_HOST="${SPRYKER_BROKER_API_HOST}" RABBITMQ_MANAGEMENT_PORT="${SPRYKER_BROKER_API_PORT:-15672}" \
    RABBITMQ_USER="${SPRYKER_BROKER_API_USERNAME}" RABBITMQ_PASS="${SPRYKER_BROKER_API_PASSWORD}" SAMPLE_INTERVAL_SECONDS="${SAMPLE_S}" \
    bash data/ps-metrics/queue-sampler.sh > "${OUT}/queue-sampler.log" 2>&1 &
SAMPLER_PIDS="$!"
( while :; do printf '{"ts":%s,"kv_keys":%s,"search_docs":%s}\n' "$(date +%s.%N)" "$(kv_keys 2>/dev/null || echo null)" "$(search_docs 2>/dev/null || echo null)"; sleep 5; done ) \
    >> "${OUT}/storage-samples.jsonl" 2>/dev/null &
SAMPLER_PIDS="${SAMPLER_PIDS} $!"
# Container resource samples: load and memory of the container the workers and the import run in.
( while :; do printf '{"ts":%s,"load1":%s,"mem_used_mb":%s,"php_procs":%s}\n' "$(date +%s.%N)" "$(cut -d' ' -f1 /proc/loadavg)" \
    "$(free -m | awk '/^Mem:/{print $3}')" "$(pgrep -c php || echo 0)"; sleep 5; done ) >> "${OUT}/service-samples.jsonl" 2>/dev/null &
SAMPLER_PIDS="${SAMPLER_PIDS} $!"

WINDOW_T0=$(date +%s.%N)
stage_start import
rc=0
if [ "${IMPORT_SPLIT}" = 1 ]; then
    for f in "${OUT}"/actions/*.yml; do
        n=$(basename "$f" .yml); t0=$(date +%s.%N)
        env ${ENV_FLAGS} php -d memory_limit="${IMPORT_MEMORY_LIMIT}" vendor/bin/console data:import --config="$f" >> "${OUT}/stage-import.log" 2>&1
        r=$?; t1=$(date +%s.%N)
        printf '{"action":"%s","start":%s,"end":%s,"exit_code":%s}\n' "$n" "$t0" "$t1" "$r" >> "${OUT}/import-actions.jsonl"
        printf '  %s %s exit=%s %.1fs\n' "$(date -u +%H:%M:%S)" "$n" "$r" "$(awk "BEGIN{print $t1 - $t0}")"
        [ $r -eq 0 ] || rc=$r
    done
else
    env ${ENV_FLAGS} php -d memory_limit="${IMPORT_MEMORY_LIMIT}" vendor/bin/console data:import --config="${OUT}/measured-import.yml" > "${OUT}/stage-import.log" 2>&1
    rc=$?
fi
stage_end $rc
[ $rc -eq 0 ] || echo "WARNING: at least one import action failed (exit ${rc}), see stage-import.log; the drain is still measured"
IMPORT_RC=$rc

stage_start drain
drain "${MAX_WAIT_S}" > "${OUT}/stage-drain.log" 2>&1
DRAIN_RC=$?
stage_end $DRAIN_RC
WINDOW_T1=$(date +%s.%N)
sleep 15   # last task records and a few idle samples
stop_samplers
rm -f "${SETTINGS_FILE}"

# --- 5. positive control and meta -----------------------------------------------------------------

say "positive control"
KV1=$(kv_keys); ES1=$(search_docs); ERR1=$(error_total)
TASKS=$( [ -f "${OUT}/task-runs.jsonl" ] && wc -l < "${OUT}/task-runs.jsonl" || echo 0 )
IMPORTS=$( [ -f "${OUT}/import-runs.jsonl" ] && wc -l < "${OUT}/import-runs.jsonl" || echo 0 )
python3 - "${OUT}/meta.json" <<PY
import json
json.dump({
 "run_id": "${RUN_ID}", "environment": "${SPRYKER_PROJECT_NAME:-sa-internal-ptest}", "data_set": "${DATA_SET}",
 "instrumented": ${INSTRUMENTED} == 1, "breakdown": ${BREAKDOWN} == 1, "import_split": ${IMPORT_SPLIT} == 1,
 "scheduler_mode": True, "sync_mode": "${SYNC_MODE}",
 "branch": "${GIT_BRANCH_NAME:-unknown}", "git_sha": "${GIT_SHA:-unknown}",
 "window_start": ${WINDOW_T0}, "window_end": ${WINDOW_T1}, "window_seconds": round(${WINDOW_T1} - ${WINDOW_T0}, 3),
 "import_exit_code": ${IMPORT_RC}, "drain_exit_code": ${DRAIN_RC},
 "kv_keys_before": ${KV0:-0}, "kv_keys": ${KV1:-0}, "es_docs_before": ${ES0:-0}, "es_docs": ${ES1:-0},
 "error_messages_before": ${ERR0:-0}, "error_messages": ${ERR1:-0},
 "task_run_records": ${TASKS}, "import_run_records": ${IMPORTS},
 "host_cpus": $(nproc), "host_mem_mb": $(free -m | awk '/^Mem:/{print $2}'),
}, open("${OUT}/meta.json", "w"), indent=1)
PY
cat "${OUT}/meta.json"

[ "${DRAIN_RC}" -eq 0 ] || fail "drain did not finish (exit ${DRAIN_RC}): $(tail -2 "${OUT}/stage-drain.log")"
[ "${KV1:-0}" -gt "${KV0:-0}" ] || fail "positive control: key-value keys did not grow (${KV0} -> ${KV1})"
[ "${ES1:-0}" -gt "${ES0:-0}" ] || fail "positive control: search documents did not grow (${ES0} -> ${ES1})"
if [ "${INSTRUMENTED}" = 1 ]; then
    [ "${TASKS}" -gt 0 ] || fail "positive control: no task-run records, the workers were not instrumented"
    [ "${IMPORTS}" -gt 0 ] || fail "positive control: no import-run records"
fi
[ "${IMPORT_RC}" -eq 0 ] || fail "import had a failed action (exit ${IMPORT_RC})"
say "run ok: ${RUN_ID}"
finish 0
