#!/usr/bin/env bash
#
# Store setup before a load, and a check that every store can serve Yves and Glue.
#
# Why (2026-11-04, b2b, ICP v2 s010 on the clean demo): the load added the stores FR, NL and CH. Search
# indices had been created for DE and AT only, before the import, so the first documents of the new stores
# created their indices by dynamic mapping (integer-facet not nested, no analyzers). Every catalog search
# of those stores failed with HTTP 500, and every later search:setup:sources failed on those indices.
# Their store storage also kept no currency and no country: it was written once, right after the store
# and locale-store actions, and the currency-store and country-store events never rewrote it. Yves and
# Glue then failed on every request with "defaultCurrencyIsoCode of StoreTransfer is null". No check of
# the runner could fail on either, so the broken state went into the snap__s010_loaded snapshot.
#
# Usage (from the project root):
#   tools/ps-metrics/store-setup.sh prepare --import-config <yml> --work-dir <dir under the project>
#       1. imports the store actions of the config (store, locale, currency, country, context) on their own
#       2. queue:setup and search:setup:sources, now that every store exists
#       3. publish:trigger-events -r store, after every store row is committed, and a drain
#       4. check (below)
#       Without store actions in the config only steps 2 to 4 run.
#   tools/ps-metrics/store-setup.sh check
#       Fails when a store has no currency or country in its storage, or when a store misses a search
#       index another store has, or has one without the analysis settings the others have (dynamic
#       mapping), or a page index whose integer-facet is not nested.
#
# Environment: CLI_CONTAINER, DB_CONTAINER, DB_NAME, DB_ROOT_USER, DB_ROOT_PASS as for the other scripts,
# WORK_QUEUE_EXCLUDE as for run-baseline.sh, DRAIN_WAIT_S (default 600).

set -euo pipefail

CONTAINER="${CLI_CONTAINER:-spryker_cli_1}"
DB_CONTAINER="${DB_CONTAINER:-spryker_database_1}"
DB_NAME="${DB_NAME:-eu-docker}"
DB_ROOT_USER="${DB_ROOT_USER:-root}"
DB_ROOT_PASS="${DB_ROOT_PASS:-secret}"
DRAIN_WAIT_S="${DRAIN_WAIT_S:-600}"
# Data import entities that create stores or the store relations kept in store storage.
STORE_ENTITIES="store locale-store default-locale-store currency-store country-store context-store"

fail() { printf 'store-setup: %s\n' "$1" >&2; exit 1; }
say()  { printf '  %s\n' "$1"; }

# `docker/sdk cli` exits 0 even when the command inside failed, so success is a marker the command
# prints only after it returned 0. The output is kept for the failure message.
console() {
    local out
    out=$(docker/sdk cli "$* && echo STEP-OK" 2>&1 || true)
    if ! printf '%s\n' "${out}" | grep -q 'STEP-OK'; then
        printf '%s\n' "${out}" | grep -vE '^#[0-9]+ ' | tail -15 >&2
        fail "'$*' failed"
    fi
}

work_total() {
    docker exec "${CONTAINER}" bash -lc "curl -s -u \${SPRYKER_BROKER_API_USERNAME:-spryker}:\${SPRYKER_BROKER_API_PASSWORD:-secret} http://broker:15672/api/queues" \
        | python3 -c 'import sys,json;ex=set(sys.argv[1].split(","));print(sum(q.get("messages",0) for q in json.load(sys.stdin) if not q["name"].endswith(".error") and q["name"] not in ex))' "${WORK_QUEUE_EXCLUDE:-scheduler-jobs}"
}

check() {
    local storage indices
    storage=$(docker exec -i "${DB_CONTAINER}" mariadb -u"${DB_ROOT_USER}" -p"${DB_ROOT_PASS}" -N -B "${DB_NAME}" \
        -e "SELECT s.name, COALESCE(ss.data, '') FROM spy_store s LEFT JOIN spy_store_storage ss ON ss.store_name = s.name") \
        || fail "could not read spy_store and spy_store_storage"
    # Three lines: index names, analysis settings, integer-facet type. filter_path drops an index without
    # the filtered key, which is the signal; the field mapping API would not do, it returns leaf fields only.
    indices=$(docker exec "${CONTAINER}" bash -lc "curl -sf 'http://search:9200/_cat/indices/spryker_*?h=index&format=json' && echo \
        && curl -sf 'http://search:9200/spryker_*/_settings?filter_path=*.settings.index.analysis' && echo \
        && curl -sf 'http://search:9200/spryker_*/_mapping?filter_path=*.mappings.properties.integer-facet.type'") \
        || fail "could not read the search indices"
    python3 - "${storage}" "${indices}" <<'PY'
import json, sys

problems = []
stores = []
for line in sys.argv[1].splitlines():
    name, _, data = line.partition('\t')
    stores.append(name)
    if not data:
        problems.append(f'{name}: no store storage row')
        continue
    entry = json.loads(data)
    if not entry.get('default_currency_iso_code'):
        problems.append(f'{name}: store storage has no default currency')
    if not entry.get('countries'):
        problems.append(f'{name}: store storage has no country')

names_raw, settings_raw, mapping_raw = (sys.argv[2].split('\n') + ['', ''])[:3]
all_indices = {row['index'] for row in json.loads(names_raw or '[]')}
settings = json.loads(settings_raw or '{}')
mappings = json.loads(mapping_raw or '{}')
# Index names end in _<store>_<source>, the prefix holds underscores itself.
by_store = {}
for store in stores:
    marker = f'_{store.lower()}_'
    by_store[store] = {i.rsplit(marker, 1)[1]: i for i in all_indices if marker in i}
sources = set().union(*by_store.values()) if by_store else set()
for source in sorted(sources):
    with_analysis = {s for s, idx in by_store.items() if source in idx and by_store[s][source] in settings}
    for store in stores:
        index = by_store[store].get(source)
        if index is None:
            problems.append(f'{store}: no {source} index')
        elif with_analysis and store not in with_analysis:
            problems.append(f'{index}: no analysis settings, created by dynamic mapping')
for store in stores:
    index = by_store[store].get('page')
    if index is None:
        continue
    kind = mappings.get(index, {}).get('mappings', {}).get('properties', {}).get('integer-facet', {}).get('type')
    if kind != 'nested':
        problems.append(f'{index}: integer-facet is {kind or "object or missing"}, not nested (dynamic mapping)')

if not stores:
    problems.append('no store in spy_store')
for problem in problems:
    print(f'  FAIL {problem}', file=sys.stderr)
if problems:
    sys.exit(1)
print(f'  stores ok: {", ".join(stores)} (storage with currency and country, search indices {", ".join(sorted(sources))})')
PY
}

prepare() {
    local config="" work_dir=""
    while [ $# -gt 0 ]; do
        case "$1" in
            --import-config) config="$2"; shift 2 ;;
            --work-dir) work_dir="$2"; shift 2 ;;
            *) fail "unknown option $1" ;;
        esac
    done
    [ -f "${config}" ] || fail "--import-config <yml> required, got '${config}'"
    [ -n "${work_dir}" ] || fail "--work-dir <dir under the project> required"
    mkdir -p "${work_dir}"

    local store_config="${work_dir}/store-setup.yml"
    python3 - "${config}" "${store_config}" "${STORE_ENTITIES}" <<'PY'
import pathlib, re, sys
config, out, wanted = pathlib.Path(sys.argv[1]), pathlib.Path(sys.argv[2]), set(sys.argv[3].split())
actions = re.findall(r'-\s*data_entity:\s*(\S+)\s*\n\s*source:\s*(\S+)', config.read_text())
picked = [(e, s) for e, s in actions if e in wanted]
if picked:
    out.write_text('version: 0\nactions:\n' + ''.join(f'  - data_entity: {e}\n    source: {s}\n' for e, s in picked))
elif out.exists():
    out.unlink()
PY
    if [ -f "${store_config}" ]; then
        # The config is written on the host; wait until the container sees it (file sync on macOS).
        for _ in $(seq 1 60); do docker exec "${CONTAINER}" test -f "/data/${store_config}" && break; sleep 1; done
        docker exec "${CONTAINER}" test -f "/data/${store_config}" || fail "${store_config} not visible in the container"
        say "importing the store actions of ${config}"
        console "vendor/bin/console data:import --config=/data/${store_config}"
    else
        say "no store actions in ${config}"
    fi

    say "queue:setup and search:setup:sources for every store"
    console "vendor/bin/console queue:setup"
    console "vendor/bin/console search:setup:sources"

    say "republishing the stores and draining"
    console "vendor/bin/console publish:trigger-events -r store"
    # The scheduler may be suspended (runner mode); this worker drains whatever the trigger queued.
    console "vendor/bin/console queue:worker:start --stop-when-empty"
    local quiet=0
    for _ in $(seq 1 "${DRAIN_WAIT_S}"); do
        if [ "$(work_total)" = "0" ]; then quiet=$((quiet + 1)); else quiet=0; fi
        [ "${quiet}" -ge 3 ] && break
        sleep 1
    done
    [ "${quiet}" -ge 3 ] || fail "queues did not drain within ${DRAIN_WAIT_S} s after the store publish"

    check
}

case "${1:-}" in
    prepare) shift; prepare "$@" ;;
    check) check ;;
    *) sed -n '2,30p' "$0" >&2; exit 2 ;;
esac
