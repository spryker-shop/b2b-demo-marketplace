#!/usr/bin/env bash
#
# SOL-629 storage size at drain end (contract C8).
#
# Writes one JSON object:
#
#   {"ts": ..., "key_value": {"used_memory": <bytes>, "used_memory_dataset": <bytes or null>,
#                              "keys_total": <int>, "databases": {"db1": {"keys": <int>, "expires": <int>}}},
#    "search": {"docs_total": <int>, "store_bytes_total": <int>,
#               "indices": {"<index>": {"docs": <int>, "store_bytes": <int>, "pri_store_bytes": <int>}}}}
#
# Reaches both stores the way the runner's positive control does, from inside the CLI container:
# key-value through `redis-cli -h key_value_store INFO` (keys per database from the keyspace section,
# never DBSIZE, which returns 0 on KVrocks, and never a bare --scan, which sees database 0 only), and
# search through `curl http://search:9200/_cat/indices` in bytes. A store that does not answer is null,
# never 0. Read-only.
#
# Usage: storage-size.sh --out FILE [--container NAME]

set -euo pipefail

OUT=""
CONTAINER="${CLI_CONTAINER:-spryker_cli_1}"
while [ $# -gt 0 ]; do
    case "$1" in
        --out) OUT="$2"; shift 2 ;;
        --container) CONTAINER="$2"; shift 2 ;;
        -h|--help) sed -n '2,/^$/p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
        *) echo "Unknown argument: $1" >&2; exit 2 ;;
    esac
done
[ -n "${OUT}" ] || { echo "--out is required" >&2; exit 2; }

# One round trip; the markers keep a failed source from shifting the other into its place.
PROBE="redis-cli -h key_value_store INFO memory 2>/dev/null; redis-cli -h key_value_store INFO keyspace 2>/dev/null; \
echo '---SEARCH---'; curl -s --max-time 15 'http://search:9200/_cat/indices?format=json&bytes=b&h=index,docs.count,store.size,pri.store.size' 2>/dev/null"
# On Kvrocks the keyspace figures are as of the last finished scan; this runs one to completion first.
"$(dirname "$0")/kv-keys.sh" --settle --container "${CONTAINER}" >/dev/null || true
RAW=$(docker exec "${CONTAINER}" bash -lc "${PROBE}" 2>/dev/null || true)

printf '%s' "${RAW}" | python3 -c '
import json, re, sys, time
raw = sys.stdin.read()
kv_part, _, search_part = raw.partition("---SEARCH---")

key_value = None
memory = re.search(r"^used_memory:(\d+)", kv_part, re.M)
if memory or "# Keyspace" in kv_part:
    databases = {m.group(1): {"keys": int(m.group(2)), "expires": int(m.group(3))}
                 for m in re.finditer(r"^(db\d+):keys=(\d+),expires=(\d+)", kv_part, re.M)}
    dataset = re.search(r"^used_memory_dataset:(\d+)", kv_part, re.M)
    key_value = {"used_memory": int(memory.group(1)) if memory else None,
                 "used_memory_dataset": int(dataset.group(1)) if dataset else None,
                 "keys_total": sum(d["keys"] for d in databases.values()) if "# Keyspace" in kv_part else None,
                 "databases": databases}

search = None
try:
    rows = json.loads(search_part.strip())
except ValueError:
    rows = None
if isinstance(rows, list):
    def num(value):
        return int(value) if isinstance(value, str) and value.isdigit() else None
    indices = {r["index"]: {"docs": num(r.get("docs.count")), "store_bytes": num(r.get("store.size")),
                            "pri_store_bytes": num(r.get("pri.store.size"))}
               for r in rows if not r["index"].startswith(".")}
    search = {"docs_total": sum(i["docs"] or 0 for i in indices.values()),
              "store_bytes_total": sum(i["store_bytes"] or 0 for i in indices.values()),
              "indices": dict(sorted(indices.items()))}

print(json.dumps({"ts": round(time.time(), 3), "key_value": key_value, "search": search}, indent=2))
' > "${OUT}"

python3 - "${OUT}" <<'SUMMARY'
import json, sys
d = json.load(open(sys.argv[1]))
kv, s = d['key_value'] or {}, d['search'] or {}
print(f"  key-value: {kv.get('keys_total')} keys, {kv.get('used_memory')} bytes used; "
      f"search: {s.get('docs_total')} docs in {len(s.get('indices', {}))} indices, {s.get('store_bytes_total')} bytes")
SUMMARY
