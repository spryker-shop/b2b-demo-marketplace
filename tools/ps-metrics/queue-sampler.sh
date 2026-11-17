#!/usr/bin/env bash
#
# SOL-629 broker sampler.
#
# Polls the RabbitMQ management API and appends one JSON object per queue per tick. This is the
# collection source for every backlog metric: depth, publish and ack rates, consumer presence and
# the projected time to empty, all of which are derived from the cumulative counters rather than
# measured directly.
#
# Deliberately standalone: it talks to the broker, not to the application, so it works unchanged
# against any Spryker project and both locally and in a cloud environment.

set -euo pipefail

HOST="${RABBITMQ_HOST:-localhost}"
PORT="${RABBITMQ_MANAGEMENT_PORT:-15672}"
USER="${RABBITMQ_USER:-spryker}"
PASS="${RABBITMQ_PASS:-secret}"
VHOST="${RABBITMQ_VHOST:-}"
INTERVAL="${SAMPLE_INTERVAL_SECONDS:-1}"
OUT="${OUT_FILE:-./data/metrics/queue-samples.jsonl}"

usage() {
    cat <<'USAGE'
Usage: queue-sampler.sh [--out FILE] [--interval SECONDS] [--duration SECONDS]

Environment:
  RABBITMQ_HOST              default localhost
  RABBITMQ_MANAGEMENT_PORT   default 15672
  RABBITMQ_USER              default spryker
  RABBITMQ_PASS              default secret
  RABBITMQ_VHOST             optional, restricts sampling to one vhost
  SAMPLE_INTERVAL_SECONDS    default 1
  OUT_FILE                   default ./data/metrics/queue-samples.jsonl

Stop with Ctrl-C, or bound the run with --duration.
USAGE
}

DURATION=""
while [ $# -gt 0 ]; do
    case "$1" in
        --out) OUT="$2"; shift 2 ;;
        --interval) INTERVAL="$2"; shift 2 ;;
        --duration) DURATION="$2"; shift 2 ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Unknown argument: $1" >&2; usage; exit 2 ;;
    esac
done

command -v curl >/dev/null || { echo "curl is required" >&2; exit 1; }
command -v jq   >/dev/null || { echo "jq is required" >&2; exit 1; }

mkdir -p "$(dirname "$OUT")"

if [ -n "$VHOST" ]; then
    # The vhost is a path segment and needs encoding; "/" is the common default.
    ENCODED_VHOST=$(printf '%s' "$VHOST" | jq -sRr @uri)
    ENDPOINT="http://${HOST}:${PORT}/api/queues/${ENCODED_VHOST}"
else
    ENDPOINT="http://${HOST}:${PORT}/api/queues"
fi

echo "Sampling ${ENDPOINT} every ${INTERVAL}s into ${OUT}" >&2

started_at=$(date +%s)

while true; do
    ts=$(date +%s.%N)

    # A transient broker error must not kill a long sampling run, so failures are recorded as a
    # marker line instead of aborting. A gap that is not explained in the data is worse than a gap.
    if ! payload=$(curl -sS --max-time 10 -u "${USER}:${PASS}" "${ENDPOINT}" 2>/dev/null); then
        printf '{"ts":%s,"error":"broker_unreachable"}\n' "$ts" >> "$OUT"
    else
        printf '%s' "$payload" | jq -c --arg ts "$ts" '
            if type == "array" then .[] else empty end
            | {
                ts: ($ts | tonumber),
                queue: .name,
                vhost: .vhost,
                depth: (.messages // 0),
                ready: (.messages_ready // 0),
                unacked: (.messages_unacknowledged // 0),
                consumers: (.consumers // 0),
                published: (.message_stats.publish // 0),
                acked: (.message_stats.ack // 0),
                redelivered: (.message_stats.redeliver // 0)
              }' >> "$OUT" || printf '{"ts":%s,"error":"parse_failed"}\n' "$ts" >> "$OUT"
    fi

    if [ -n "$DURATION" ]; then
        now=$(date +%s)
        [ $((now - started_at)) -ge "$DURATION" ] && break
    fi

    sleep "$INTERVAL"
done

echo "Sampling finished: $OUT" >&2
