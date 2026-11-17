#!/usr/bin/env bash
#
# SOL-629 measurement campaign (contract C5).
#
# Runs run-baseline.sh N times instrumented and M times without instrumentation, interleaved
# (for example I,U,I,I,U,I), so slow drift in the environment (cache warm-up, disk, a noisy host)
# lands on both sides instead of biasing the overhead figure. Every run gets its own bundle under
# data/metrics/campaign-<id>/<run_id>/. A failed run is recorded in campaign.json and the campaign
# carries on; the report says which runs failed. Renders the campaign report at the end.
#
# Usage:
#   run-campaign.sh [--instrumented N] [--uninstrumented M] [--id ID] [--order I,U,I,...]
#                   [--scales 0.25,0.5,0.75,1.0] [--notes TEXT] [--pause SECONDS] [--dry-run]
#
# --scales (contract C8) runs the I/U order once per scale, so N and M count per scale. The scales are
# interleaved: round k runs position k of the order at every scale, and the scale order rotates by
# one each round, so neither drift nor warm-up lands on one size. Each scale below 1.0 imports a
# subset built by scale-import.py (built before the first run, reused when up to date); 1.0 imports
# IMPORT_CONFIG itself. Every run records its scale in campaign.json and in its meta.json.
#
# Environment passed through to every run: IMPORT_CONFIG (the full set; default
# data/import/local/full_EU.yml), QUEUE_MESSAGE_CHUNK_SIZE_MAP, QUEUE_METRICS_IMPORT_ROWS,
# QUEUE_METRICS_BREAKDOWN (instrumented runs only, default 1), PUBLISH_TRIGGER, PUBLISH_RESOURCES, SYNC_DATA, IMPORT, RESET_DB, SCHEDULER, CLI_CONTAINER, RABBITMQ_*.
#
# --dry-run prints the plan and the exact commands and touches nothing: no directory, no file, no
# container, no broker.

set -euo pipefail

INSTRUMENTED_RUNS=3
UNINSTRUMENTED_RUNS=1
CAMPAIGN_ID=""
ORDER=""
NOTES=""
PAUSE=10
DRY_RUN=0
SCALES=""
BASE_CONFIG="${IMPORT_CONFIG:-data/import/local/full_EU.yml}"

usage() { sed -n '2,/^$/p' "$0" | sed 's/^# \{0,1\}//'; }

while [ $# -gt 0 ]; do
    case "$1" in
        --instrumented) INSTRUMENTED_RUNS="$2"; shift 2 ;;
        --uninstrumented) UNINSTRUMENTED_RUNS="$2"; shift 2 ;;
        --id) CAMPAIGN_ID="$2"; shift 2 ;;
        --order) ORDER="$2"; shift 2 ;;
        --notes) NOTES="$2"; shift 2 ;;
        --pause) PAUSE="$2"; shift 2 ;;
        --scales) SCALES="$2"; shift 2 ;;
        --dry-run) DRY_RUN=1; shift ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Unknown argument: $1" >&2; usage >&2; exit 2 ;;
    esac
done

case "${INSTRUMENTED_RUNS}${UNINSTRUMENTED_RUNS}${PAUSE}" in
    *[!0-9]*) echo "--instrumented, --uninstrumented and --pause take whole numbers" >&2; exit 2 ;;
esac

CAMPAIGN_ID="${CAMPAIGN_ID:-$(date +%Y-%m-%d-run-%H%M%S)}"
CAMPAIGN_DIR="data/metrics/campaign-${CAMPAIGN_ID}"
HERE="$(cd "$(dirname "$0")" && pwd)"
# Bundle paths are relative to the project root, because run-baseline.sh maps them to /data/<path>
# inside the container.
cd "${HERE}/../.."

# The plan: an explicit --order wins; otherwise the M uninstrumented runs are spread evenly through
# the sequence, never first, so neither side gets all the cold-start runs.
if [ -z "${ORDER}" ]; then
    ORDER=$(python3 - "${INSTRUMENTED_RUNS}" "${UNINSTRUMENTED_RUNS}" <<'PLAN'
import sys
n, m = int(sys.argv[1]), int(sys.argv[2])
total = n + m
slots = ['I'] * total
for k in range(m):
    position = int((k + 0.5) * total / m)
    if n:
        position = max(position, 1)
    while slots[position] == 'U':
        position = (position + 1) % total
    slots[position] = 'U'
print(','.join(slots))
PLAN
)
fi

IFS=',' read -r -a PLAN <<< "${ORDER}"
for kind in "${PLAN[@]}"; do
    case "${kind}" in I|U) ;; *) echo "--order takes I and U only, got '${kind}'" >&2; exit 2 ;; esac
done
[ "${#PLAN[@]}" -gt 0 ] || { echo "empty plan" >&2; exit 2; }

# Expand the plan over the scales: one line per run, "<kind> <scale>". Without --scales every run has
# the scale of the environment (SCALE, default 1.0) and the plan is unchanged.
SCALE_LIST="${SCALES:-${SCALE:-1.0}}"
EXPANDED=$(python3 - "${ORDER}" "${SCALE_LIST}" <<'EXPAND'
import sys
order = sys.argv[1].split(',')
try:
    scales = [float(s) for s in sys.argv[2].split(',') if s.strip()]
except ValueError:
    sys.exit(f'--scales takes numbers, got {sys.argv[2]}')
if not scales or any(not 0 < s <= 1 for s in scales) or len(set(scales)) != len(scales):
    sys.exit(f'--scales takes distinct numbers in (0, 1], got {sys.argv[2]}')
for round_index, kind in enumerate(order):
    shift = round_index % len(scales)
    for scale in scales[shift:] + scales[:shift]:
        print(kind, f'{scale:g}')
EXPAND
) || exit 2

KINDS=()
RUN_SCALES=()
while read -r kind scale; do
    KINDS+=("${kind}")
    RUN_SCALES+=("${scale}")
done <<< "${EXPANDED}"

# Config per scale: the full set at 1.0, the subset scale-import.py builds below it.
config_for() {
    if python3 -c 'import sys; sys.exit(0 if float(sys.argv[1]) == 1 else 1)' "$1"; then
        printf '%s' "${BASE_CONFIG}"
    else
        printf 'data/metrics/scaled/%s-%s/import.yml' "$(basename "${BASE_CONFIG}" .yml)" "$1"
    fi
}
scale_tag() { python3 -c 'import sys; print(f"s{round(float(sys.argv[1]) * 100):03d}")' "$1"; }

run_ids=()
i=0
for kind in "${KINDS[@]}"; do
    tag=""
    [ -n "${SCALES}" ] && tag="-$(scale_tag "${RUN_SCALES[$i]}")"
    run_ids+=("$(printf 'r%02d%s-%s' "$((i + 1))" "${tag}" "$([ "${kind}" = I ] && echo instr || echo plain)")")
    i=$((i + 1))
done
ORDER_ALL=$(IFS=','; echo "${KINDS[*]}")

printf 'Campaign %s: %s runs, order %s\n' "${CAMPAIGN_ID}" "${#KINDS[@]}" "${ORDER_ALL}"
[ -n "${SCALES}" ] && printf 'Scales: %s (order %s per scale, interleaved)\n' "${SCALES}" "${ORDER}"
printf 'Directory: %s\n' "${CAMPAIGN_DIR}"
if [ -n "${SCALES}" ]; then
    for scale in $(printf '%s\n' "${RUN_SCALES[@]}" | sort -u); do
        python3 -c 'import sys; sys.exit(0 if float(sys.argv[1]) == 1 else 1)' "${scale}" && continue
        printf '  build: python3 tools/ps-metrics/scale-import.py build --fraction %s --config %s\n' "${scale}" "${BASE_CONFIG}"
    done
fi
i=0
for kind in "${KINDS[@]}"; do
    flag=$([ "${kind}" = I ] && echo 1 || echo 0)
    printf '  %s  INSTRUMENTED=%s SCALE=%s IMPORT_CONFIG=%s RUN_ID=%s OUT_DIR=%s/%s %s/run-baseline.sh\n' \
        "${run_ids[$i]}" "${flag}" "${RUN_SCALES[$i]}" "$(config_for "${RUN_SCALES[$i]}")" \
        "${run_ids[$i]}" "${CAMPAIGN_DIR}" "${run_ids[$i]}" "tools/ps-metrics"
    i=$((i + 1))
done
printf '  then: python3 tools/ps-metrics/render-campaign.py %s\n' "${CAMPAIGN_DIR}"

if [ "${DRY_RUN}" = "1" ]; then
    printf '\nDry run: nothing was executed or written.\n'
    exit 0
fi

[ -e "${CAMPAIGN_DIR}" ] && { echo "refusing to reuse ${CAMPAIGN_DIR}: it already exists" >&2; exit 1; }

# Subsets first: a subset that cannot be built consistently stops the campaign before any run.
if [ -n "${SCALES}" ]; then
    for scale in $(printf '%s\n' "${RUN_SCALES[@]}" | sort -u); do
        python3 -c 'import sys; sys.exit(0 if float(sys.argv[1]) == 1 else 1)' "${scale}" && continue
        python3 "${HERE}/scale-import.py" build --fraction "${scale}" --config "${BASE_CONFIG}" \
            || { echo "subset at scale ${scale} could not be built" >&2; exit 1; }
    done
fi
mkdir -p "${CAMPAIGN_DIR}"

# campaign.json is rewritten after every run, so an interrupted campaign still describes what ran.
record() {
    python3 - "${CAMPAIGN_DIR}/campaign.json" "$@" <<'REC'
import json, pathlib, sys, time
path = pathlib.Path(sys.argv[1])
action = sys.argv[2]
if action == 'init':
    campaign_id, order, notes, scales = sys.argv[3], sys.argv[4], sys.argv[5], sys.argv[6]
    data = {'id': campaign_id, 'started': time.strftime('%Y-%m-%dT%H:%M:%S%z'), 'notes': notes,
            'order': order.split(','), 'runs': []}
    if scales:
        data['scales'] = [float(s) for s in scales.split(',')]
else:
    data = json.loads(path.read_text())
    run_id, instrumented, status, code, started, ended, scale, config = sys.argv[3:11]
    data['runs'].append({'run_id': run_id, 'instrumented': instrumented == '1', 'bundle': run_id,
                         'status': status, 'exit_code': int(code),
                         'started': int(started), 'ended': int(ended),
                         'scale': float(scale), 'import_config': config})
    data['finished'] = time.strftime('%Y-%m-%dT%H:%M:%S%z')
path.write_text(json.dumps(data, indent=2) + '\n')
REC
}

record init "${CAMPAIGN_ID}" "${ORDER_ALL}" "${NOTES}" "${SCALES}"

failed=0
i=0
for kind in "${KINDS[@]}"; do
    run_id="${run_ids[$i]}"
    scale="${RUN_SCALES[$i]}"
    config="$(config_for "${scale}")"
    flag=$([ "${kind}" = I ] && echo 1 || echo 0)
    printf '\n##### %s/%s  %s  (instrumented=%s, scale=%s) #####\n' "$((i + 1))" "${#KINDS[@]}" "${run_id}" "${flag}" "${scale}"
    started=$(date +%s)
    set +e
    INSTRUMENTED="${flag}" SCALE="${scale}" IMPORT_CONFIG="${config}" RUN_ID="${run_id}" OUT_DIR="${CAMPAIGN_DIR}/${run_id}" \
        "${HERE}/run-baseline.sh" 2>&1 | tee "${CAMPAIGN_DIR}/${run_id}.log"
    code=${PIPESTATUS[0]}
    set -e
    ended=$(date +%s)
    if [ "${code}" = "0" ]; then
        status=ok
        # A run that finished with messages in error queues is kept but marked, so the reports can show it.
        if python3 -c 'import json,sys;sys.exit(0 if json.load(open(sys.argv[1])).get("error_messages") else 1)' \
            "${CAMPAIGN_DIR}/${run_id}/meta.json" 2>/dev/null; then
            status=errors
            echo "RUN WITH ERRORS: ${run_id} has messages in error queues, kept"
        fi
        if [ "${flag}" = "1" ]; then
            # Writes a new report-<UTC stamp>-<sha>.html beside the bundle; an existing report is never replaced.
            python3 "${HERE}/render-report.py" "${CAMPAIGN_DIR}/${run_id}" >/dev/null 2>&1 \
                || echo "WARNING: per-run report for ${run_id} did not render"
        fi
    else
        status=failed
        failed=$((failed + 1))
        echo "RUN FAILED: ${run_id} exited ${code}, continuing with the next run"
    fi
    record run "${run_id}" "${flag}" "${status}" "${code}" "${started}" "${ended}" "${scale}" "${config}"
    i=$((i + 1))
    if [ "${i}" -lt "${#KINDS[@]}" ] && [ "${PAUSE}" -gt 0 ]; then
        sleep "${PAUSE}"
    fi
done

# A new versioned report-<UTC stamp>-<sha>.html every time; earlier renders stay as they are.
python3 "${HERE}/render-campaign.py" "${CAMPAIGN_DIR}"
printf '\nCampaign %s finished: %s of %s runs failed\n' "${CAMPAIGN_ID}" "${failed}" "${#KINDS[@]}"
